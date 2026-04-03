<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\CompleteConfiguration;

use DateTimeImmutable;
use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Errors\InvalidConfigurationTransition;
use Src\Products\Domain\Events\ProductConfigurationCompleted;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ConfigurationFlowResolver;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

final class CompleteConfigurationHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ConfigurationFlowResolver $flowResolver,
        private readonly ProductConfigStatusService $configStatusService,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof CompleteConfigurationCommand);

        return $this->transaction->run(function () use ($command): ProductResource {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            if ($product->configurationStatus->value === ConfigurationStatus::COMPLETED) {
                return ProductResource::fromEntity($product);
            }

            if ($product->targetUrl === null) {
                throw InvalidConfigurationTransition::missingTargetUrl($product->id);
            }

            $completedStatus = ConfigurationStatus::from(ConfigurationStatus::COMPLETED);

            if (!$this->flowResolver->canSkipTo($product->model->value, $product->configurationStatus, $completedStatus)) {
                throw InvalidConfigurationTransition::cannotComplete($product->id, $product->configurationStatus->value);
            }

            $this->configStatusService->transition($product, ConfigurationStatus::COMPLETED);
            $product->recordEvent(new ProductConfigurationCompleted(
                productId: $product->id,
                model: $product->model->value,
                completedAt: new DateTimeImmutable(),
            ));

            return ProductResource::fromEntity($this->productRepository->save($product));
        });
    }
}
