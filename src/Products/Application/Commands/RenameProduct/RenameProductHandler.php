<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RenameProduct;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductRenameService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class RenameProductHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductRenameService $renameService,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof RenameProductCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        $this->renameService->rename($product, $command->name);

        return ProductResource::fromEntity($this->productRepository->save($product));
    }
}
