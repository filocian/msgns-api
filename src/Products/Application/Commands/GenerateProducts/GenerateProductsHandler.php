<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\GenerateProducts;

use DateTimeImmutable;
use DateTimeZone;
use Src\Products\Domain\DataTransfer\GenerationHistorySummaryItem;
use Src\Products\Domain\DataTransfer\GeneratedProductsResult;
use Src\Products\Domain\DataTransfer\GenerateProductsInputItem;
use Src\Products\Domain\Entities\GenerationHistory;
use Src\Products\Domain\Ports\ExcelExportPort;
use Src\Products\Domain\Ports\GenerationHistoryRepositoryPort;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Entities\ProductType;
use Src\Products\Domain\Services\ProductGenerationService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

final class GenerateProductsHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductTypeRepository $productTypeRepo,
        private readonly ProductRepositoryPort $productRepo,
        private readonly ProductGenerationService $generationService,
        private readonly TransactionPort $transaction,
        private readonly ExcelExportPort $excelExporter,
        private readonly GenerationHistoryRepositoryPort $historyRepo,
    ) {}

    public function handle(Command $command): GeneratedProductsResult
    {
        assert($command instanceof GenerateProductsCommand);

        // 1. Batch-resolve all requested type IDs
        $requestedIds = array_map(
            static fn (GenerateProductsInputItem $item): int => $item->typeId,
            $command->items,
        );
        $requestedIds = array_values(array_unique($requestedIds));

        $types = $this->productTypeRepo->findByIds($requestedIds);

        /** @var array<int, \Src\Products\Domain\Entities\ProductType> $typeMap */
        $typeMap = [];
        foreach ($types as $type) {
            $typeMap[$type->id] = $type;
        }

        // 2. Validate all-or-nothing: every typeId must be found
        $missingIds = array_values(array_filter(
            $requestedIds,
            static fn (int $id): bool => !isset($typeMap[$id]),
        ));

        if ($missingIds !== []) {
            throw ValidationFailed::because('invalid_product_type_ids', [
                'invalid_ids' => $missingIds,
            ]);
        }

        // 3. Build Product entities (domain service, no side effects yet)
        $products = $this->generationService->buildProducts(
            typeMap: $typeMap,
            items: $command->items,
            passwordLength: $command->passwordLength,
        );

        if ($products === []) {
            return new GeneratedProductsResult(totalCount: 0, productsByTypeCode: []);
        }

        // 4. Persist inside a DB transaction (chunked insert → fetch IDs → update names)
        /** @var GeneratedProductsResult $result */
        $result = $this->transaction->run(function () use ($products, $command, $typeMap): GeneratedProductsResult {
            // Insert and get assigned IDs in insertion order
            $ids = $this->productRepo->bulkInsertAndReturnIds($products, chunkSize: 1000);

            if (count($ids) !== count($products)) {
                throw new \RuntimeException(sprintf(
                    'ID count mismatch: expected %d, got %d',
                    count($products),
                    count($ids),
                ));
            }

            // Rebuild product list with DB-assigned IDs and generate names
            /** @var list<\Src\Products\Domain\Entities\Product> $hydrated */
            $hydrated = [];
            /** @var array<int, string> $idToName */
            $idToName = [];

            foreach ($products as $index => $product) {
                $withId = $product->withAssignedId($ids[$index]);
                $withId->generateDefaultName();
                $hydrated[] = $withId;
                $idToName[$withId->id] = $withId->name->value;
            }

            // Batch-update names in one query
            $this->productRepo->bulkUpdateNames($idToName);

            $result = $this->generationService->buildResult($hydrated, $command->frontUrl);

            $excelBytes = $this->excelExporter->generateBytes($result);
            $summary = $this->buildHistorySummary($command->items, $typeMap);

            $this->historyRepo->save(GenerationHistory::create(
                generatedAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
                totalCount: $result->totalCount,
                summary: $summary,
                excelBlob: $excelBytes,
                generatedById: $command->userId,
            ));

            return $result;
        });

        return $result;
    }

    /**
     * @param list<GenerateProductsInputItem> $items
     * @param array<int, ProductType> $typeMap
     * @return list<GenerationHistorySummaryItem>
     */
    private function buildHistorySummary(array $items, array $typeMap): array
    {
        return array_map(function (GenerateProductsInputItem $item) use ($typeMap): GenerationHistorySummaryItem {
            $type = $typeMap[$item->typeId];

            return new GenerationHistorySummaryItem(
                typeCode: $type->code->value,
                typeName: $type->name,
                quantity: $item->quantity,
                size: $item->size,
                description: $item->description ?? $type->description,
            );
        }, $items);
    }
}
