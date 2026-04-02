<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\GenerateProducts\GenerateProductsCommand;
use Src\Products\Application\Commands\GenerateProducts\GenerateProductsHandler;
use Src\Products\Domain\DataTransfer\GenerateProductsInputItem;
use Src\Products\Domain\Entities\GenerationHistory;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\ProductType;
use Src\Products\Domain\Ports\ExcelExportPort;
use Src\Products\Domain\Ports\GenerationHistoryRepositoryPort;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Services\ProductGenerationService;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

afterEach(fn () => Mockery::close());

function makeHistoryProductType(int $id, string $code, string $name, string $primary, ?string $secondary = null, ?string $description = null): ProductType
{
    return ProductType::fromPersistence(
        id: $id,
        code: $code,
        name: $name,
        primaryModel: $primary,
        secondaryModel: $secondary,
        createdAt: new DateTimeImmutable('2026-04-02T12:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2026-04-02T12:00:00+00:00'),
        description: $description,
    );
}

describe('GenerateProductsHandler', function () {
    it('passes userId to GenerationHistory entity when userId is present', function () {
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        /** @var MockInterface&ExcelExportPort $excelExporter */
        $excelExporter = Mockery::mock(ExcelExportPort::class);
        /** @var MockInterface&GenerationHistoryRepositoryPort $historyRepo */
        $historyRepo = Mockery::mock(GenerationHistoryRepositoryPort::class);

        $type = makeHistoryProductType(1, 'QR_BASIC', 'QR Basic', 'QR_BASIC');
        $command = new GenerateProductsCommand(
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 2)],
            frontUrl: 'https://front.example.com',
            passwordLength: 12,
            userId: 7,
        );

        $typeRepo->shouldReceive('findByIds')->once()->with([1])->andReturn([$type]);
        $productRepo->shouldReceive('bulkInsertAndReturnIds')->once()->andReturn([100, 101]);
        $productRepo->shouldReceive('bulkUpdateNames')->once();
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());
        $excelExporter->shouldReceive('generateBytes')->once()->andReturn('xlsx-binary');
        $historyRepo->shouldReceive('save')->once()->with(Mockery::on(function (GenerationHistory $history): bool {
            expect($history->generatedById)->toBe(7)
                ->and($history->totalCount)->toBe(2)
                ->and($history->excelBlob)->toBe('xlsx-binary');

            return true;
        }));

        $handler = new GenerateProductsHandler(
            productTypeRepo: $typeRepo,
            productRepo: $productRepo,
            generationService: new ProductGenerationService(new class implements \Src\Products\Domain\Ports\PasswordGeneratorPort {
                public function generate(int $length): string { return 'secret'; }
            }),
            transaction: $transaction,
            excelExporter: $excelExporter,
            historyRepo: $historyRepo,
        );

        $result = $handler->handle($command);

        expect($result->totalCount)->toBe(2);
    });

    it('passes null userId to GenerationHistory entity when userId is absent', function () {
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        /** @var MockInterface&ExcelExportPort $excelExporter */
        $excelExporter = Mockery::mock(ExcelExportPort::class);
        /** @var MockInterface&GenerationHistoryRepositoryPort $historyRepo */
        $historyRepo = Mockery::mock(GenerationHistoryRepositoryPort::class);

        $type = makeHistoryProductType(1, 'QR_BASIC', 'QR Basic', 'QR_BASIC');

        $typeRepo->shouldReceive('findByIds')->once()->andReturn([$type]);
        $productRepo->shouldReceive('bulkInsertAndReturnIds')->once()->andReturn([100]);
        $productRepo->shouldReceive('bulkUpdateNames')->once();
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());
        $excelExporter->shouldReceive('generateBytes')->once()->andReturn('xlsx-binary');
        $historyRepo->shouldReceive('save')->once()->with(Mockery::on(function (GenerationHistory $history): bool {
            expect($history->generatedById)->toBeNull();

            return true;
        }));

        $handler = new GenerateProductsHandler(
            productTypeRepo: $typeRepo,
            productRepo: $productRepo,
            generationService: new ProductGenerationService(new class implements \Src\Products\Domain\Ports\PasswordGeneratorPort {
                public function generate(int $length): string { return 'secret'; }
            }),
            transaction: $transaction,
            excelExporter: $excelExporter,
            historyRepo: $historyRepo,
        );

        $handler->handle(new GenerateProductsCommand(
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 1)],
            frontUrl: 'https://front.example.com',
            passwordLength: 12,
        ));

        expect(true)->toBeTrue();
    });

    it('persists GenerationHistory with correct totalCount summary entries and blob', function () {
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        /** @var MockInterface&ExcelExportPort $excelExporter */
        $excelExporter = Mockery::mock(ExcelExportPort::class);
        /** @var MockInterface&GenerationHistoryRepositoryPort $historyRepo */
        $historyRepo = Mockery::mock(GenerationHistoryRepositoryPort::class);

        $type = makeHistoryProductType(1, 'QR_BASIC', 'QR Basic', 'QR_BASIC', null, 'Fallback desc');

        $typeRepo->shouldReceive('findByIds')->once()->andReturn([$type]);
        $productRepo->shouldReceive('bulkInsertAndReturnIds')->once()->andReturn([100, 101, 102]);
        $productRepo->shouldReceive('bulkUpdateNames')->once();
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());
        $excelExporter->shouldReceive('generateBytes')->once()->andReturn('xlsx-binary');
        $historyRepo->shouldReceive('save')->once()->with(Mockery::on(function (GenerationHistory $history): bool {
            expect($history->totalCount)->toBe(3)
                ->and($history->summaryToArray())->toBe([[ 
                    'type_code' => 'QR_BASIC',
                    'type_name' => 'QR Basic',
                    'quantity' => 3,
                    'size' => 'M',
                    'description' => 'Custom desc',
                ]])
                ->and($history->excelBlob)->toBe('xlsx-binary');

            return true;
        }));

        $handler = new GenerateProductsHandler(
            productTypeRepo: $typeRepo,
            productRepo: $productRepo,
            generationService: new ProductGenerationService(new class implements \Src\Products\Domain\Ports\PasswordGeneratorPort {
                public function generate(int $length): string { return 'secret'; }
            }),
            transaction: $transaction,
            excelExporter: $excelExporter,
            historyRepo: $historyRepo,
        );

        $handler->handle(new GenerateProductsCommand(
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 3, size: 'M', description: 'Custom desc')],
            frontUrl: 'https://front.example.com',
            passwordLength: 12,
            userId: 1,
        ));

        expect(true)->toBeTrue();
    });

    it('builds summary from command items and typeMap and keeps dual model quantity undoubled', function () {
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        /** @var MockInterface&ExcelExportPort $excelExporter */
        $excelExporter = Mockery::mock(ExcelExportPort::class);
        /** @var MockInterface&GenerationHistoryRepositoryPort $historyRepo */
        $historyRepo = Mockery::mock(GenerationHistoryRepositoryPort::class);

        $dualType = makeHistoryProductType(1, 'NFC_PRO', 'NFC Pro', 'NFC_PRO primary', 'NFC_PRO secondary', 'Type desc');

        $typeRepo->shouldReceive('findByIds')->once()->andReturn([$dualType]);
        $productRepo->shouldReceive('bulkInsertAndReturnIds')->once()->andReturn([100, 101, 102, 103]);
        $productRepo->shouldReceive('bulkUpdateNames')->once();
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());
        $excelExporter->shouldReceive('generateBytes')->once()->andReturn('xlsx-binary');
        $historyRepo->shouldReceive('save')->once()->with(Mockery::on(function (GenerationHistory $history): bool {
            expect($history->totalCount)->toBe(4)
                ->and($history->summaryToArray()[0]['quantity'])->toBe(2)
                ->and($history->summaryToArray()[0]['type_code'])->toBe('NFC_PRO');

            return true;
        }));

        $handler = new GenerateProductsHandler(
            productTypeRepo: $typeRepo,
            productRepo: $productRepo,
            generationService: new ProductGenerationService(new class implements \Src\Products\Domain\Ports\PasswordGeneratorPort {
                public function generate(int $length): string { return 'secret'; }
            }),
            transaction: $transaction,
            excelExporter: $excelExporter,
            historyRepo: $historyRepo,
        );

        $handler->handle(new GenerateProductsCommand(
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 2)],
            frontUrl: 'https://front.example.com',
            passwordLength: 12,
            userId: 1,
        ));

        expect(true)->toBeTrue();
    });

    it('calls excelExporter generateBytes inside the transaction', function () {
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        /** @var MockInterface&ExcelExportPort $excelExporter */
        $excelExporter = Mockery::mock(ExcelExportPort::class);
        /** @var MockInterface&GenerationHistoryRepositoryPort $historyRepo */
        $historyRepo = Mockery::mock(GenerationHistoryRepositoryPort::class);

        $typeRepo->shouldReceive('findByIds')->once()->andReturn([makeHistoryProductType(1, 'QR_BASIC', 'QR Basic', 'QR_BASIC')]);
        $productRepo->shouldReceive('bulkInsertAndReturnIds')->once()->andReturn([100]);
        $productRepo->shouldReceive('bulkUpdateNames')->once();

        $insideTransaction = false;
        $transaction->shouldReceive('run')->once()->andReturnUsing(function (callable $callback) use (&$insideTransaction): mixed {
            $insideTransaction = true;

            try {
                return $callback();
            } finally {
                $insideTransaction = false;
            }
        });

        $excelExporter->shouldReceive('generateBytes')->once()->andReturnUsing(function () use (&$insideTransaction): string {
            expect($insideTransaction)->toBeTrue();

            return 'xlsx-binary';
        });
        $historyRepo->shouldReceive('save')->once();

        $handler = new GenerateProductsHandler(
            productTypeRepo: $typeRepo,
            productRepo: $productRepo,
            generationService: new ProductGenerationService(new class implements \Src\Products\Domain\Ports\PasswordGeneratorPort {
                public function generate(int $length): string { return 'secret'; }
            }),
            transaction: $transaction,
            excelExporter: $excelExporter,
            historyRepo: $historyRepo,
        );

        $handler->handle(new GenerateProductsCommand(
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 1)],
            frontUrl: 'https://front.example.com',
            passwordLength: 12,
        ));

        expect(true)->toBeTrue();
    });

    it('throws invalid_product_type_ids when any requested type is missing', function () {
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        /** @var MockInterface&ExcelExportPort $excelExporter */
        $excelExporter = Mockery::mock(ExcelExportPort::class);
        /** @var MockInterface&GenerationHistoryRepositoryPort $historyRepo */
        $historyRepo = Mockery::mock(GenerationHistoryRepositoryPort::class);

        $typeRepo->shouldReceive('findByIds')->once()->with([1])->andReturn([]);
        $productRepo->shouldNotReceive('bulkInsertAndReturnIds');
        $productRepo->shouldNotReceive('bulkUpdateNames');
        $excelExporter->shouldNotReceive('generateBytes');
        $historyRepo->shouldNotReceive('save');

        $handler = new GenerateProductsHandler(
            productTypeRepo: $typeRepo,
            productRepo: $productRepo,
            generationService: new ProductGenerationService(new class implements \Src\Products\Domain\Ports\PasswordGeneratorPort {
                public function generate(int $length): string { return 'secret'; }
            }),
            transaction: $transaction,
            excelExporter: $excelExporter,
            historyRepo: $historyRepo,
        );

        expect(fn () => $handler->handle(new GenerateProductsCommand(
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 1)],
            frontUrl: 'https://front.example.com',
            passwordLength: 12,
        )))->toThrow(ValidationFailed::class, 'invalid_product_type_ids');
    });
});
