<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ConfigureUrlProduct;

use DateTimeImmutable;
use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Events\ProductConfigurationCompleted;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ConfigurationFlowResolver;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\Services\ProductConfigurationService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

final class ConfigureUrlProductHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductConfigurationService $configurationService,
        private readonly ProductConfigStatusService $configStatusService,
        private readonly ConfigurationFlowResolver $flowResolver,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof ConfigureUrlProductCommand);

        return $this->transaction->run(function () use ($command): ProductResource {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            $this->configurationService->setTargetUrl($product, $command->targetUrl);

            if ($product->configurationStatus->value === ConfigurationStatus::COMPLETED) {
                $product->recordEvent(new ProductConfigurationCompleted(
                    productId: $product->id,
                    model: $product->model->value,
                    completedAt: new DateTimeImmutable(),
                ));

                return ProductResource::fromEntity($this->productRepository->save($product));
            }

            $nextState = $this->flowResolver->nextState($product->model->value, $product->configurationStatus);

            if ($nextState !== null && $product->configurationStatus->canTransitionTo($nextState)) {
                $this->configStatusService->transition($product, $nextState->value);
            }

            $completionState = $this->flowResolver->nextState($product->model->value, $product->configurationStatus);

            if ($completionState?->value === ConfigurationStatus::COMPLETED
                && $product->configurationStatus->canTransitionTo($completionState)) {
                $this->configStatusService->transition($product, $completionState->value);
                $product->recordEvent(new ProductConfigurationCompleted(
                    productId: $product->id,
                    model: $product->model->value,
                    completedAt: new DateTimeImmutable(),
                ));
            }

            return ProductResource::fromEntity($this->productRepository->save($product));
        });
    }
}
