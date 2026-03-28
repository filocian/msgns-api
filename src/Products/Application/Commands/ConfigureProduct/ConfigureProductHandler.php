<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ConfigureProduct;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\Services\ProductConfigurationService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

final class ConfigureProductHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductConfigurationService $configurationService,
        private readonly ProductConfigStatusService $configStatusService,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof ConfigureProductCommand);

        return $this->transaction->run(function () use ($command): ProductResource {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            $this->configurationService->setTargetUrl($product, $command->targetUrl);

            try {
                $this->configStatusService->transition($product, 'target-set');
            } catch (\InvalidArgumentException) {
                // Already advanced beyond target-set, keep current status.
            }

            return ProductResource::fromEntity($this->productRepository->save($product));
        });
    }
}
