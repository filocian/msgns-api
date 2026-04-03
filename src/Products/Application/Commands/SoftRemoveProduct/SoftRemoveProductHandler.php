<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\SoftRemoveProduct;

use Src\Products\Domain\Events\ProductSoftDeleted;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductLifecycleService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;

final class SoftRemoveProductHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductLifecycleService $lifecycleService,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): null
    {
        assert($command instanceof SoftRemoveProductCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        $this->lifecycleService->softDelete($command->productId);
        $this->eventBus->publish(new ProductSoftDeleted(productId: $command->productId));

        return null;
    }
}
