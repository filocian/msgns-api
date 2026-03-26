<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RestoreProduct;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductLifecycleService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class RestoreProductHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductLifecycleService $lifecycleService,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof RestoreProductCommand);

        $product = $this->productRepository->findByIdWithTrashed($command->productId);

        if ($product === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        $this->lifecycleService->restore($command->productId);

        $fresh = $this->productRepository->findById($command->productId);

        if ($fresh === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        return ProductResource::fromEntity($fresh);
    }
}
