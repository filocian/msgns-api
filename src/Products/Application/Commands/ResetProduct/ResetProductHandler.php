<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ResetProduct;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductResetService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class ResetProductHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductResetService $resetService,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof ResetProductCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        $this->resetService->reset($product);

        return ProductResource::fromEntity($this->productRepository->save($product));
    }
}
