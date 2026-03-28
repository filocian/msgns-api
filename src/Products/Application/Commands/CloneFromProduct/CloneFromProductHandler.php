<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\CloneFromProduct;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductCloneService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

final class CloneFromProductHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductCloneService $cloneService,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof CloneFromProductCommand);

        return $this->transaction->run(function () use ($command): ProductResource {
            $targetProduct = $this->productRepository->findById($command->targetId);

            if ($targetProduct === null) {
                throw NotFound::entity('product', (string) $command->targetId);
            }

            $sourceProduct = $this->productRepository->findById($command->sourceId);

            if ($sourceProduct === null) {
                throw NotFound::entity('product', (string) $command->sourceId);
            }

            if ($sourceProduct->productTypeId !== $targetProduct->productTypeId) {
                throw ValidationFailed::because('products_must_have_same_type', [
                    'source_product_type_id' => $sourceProduct->productTypeId,
                    'target_product_type_id' => $targetProduct->productTypeId,
                ]);
            }

            $this->cloneService->clone($sourceProduct, $targetProduct);

            return ProductResource::fromEntity($this->productRepository->save($targetProduct));
        });
    }
}
