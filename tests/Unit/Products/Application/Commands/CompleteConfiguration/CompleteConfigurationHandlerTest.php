<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\CompleteConfiguration\CompleteConfigurationCommand;
use Src\Products\Application\Commands\CompleteConfiguration\CompleteConfigurationHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Errors\InvalidConfigurationTransition;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ConfigurationFlowResolver;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Ports\TransactionPort;

function completableProduct(string $status = ConfigurationStatus::TARGET_SET, ?string $targetUrl = 'https://example.com'): Product
{
    return Product::fromPersistence(
        id: 42,
        productTypeId: 1,
        userId: 7,
        model: 'google',
        linkedToProductId: null,
        password: 'secret',
        targetUrl: $targetUrl,
        usage: 0,
        name: 'Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::from($status),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('CompleteConfigurationHandler', function () {
    it('completes target-set products', function () {
        $product = completableProduct();
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new CompleteConfigurationHandler($repo, new ConfigurationFlowResolver(), new ProductConfigStatusService(), $transaction);

        expect($handler->handle(new CompleteConfigurationCommand(42))->configurationStatus)->toBe(ConfigurationStatus::COMPLETED);
    });

    it('completes business-set products without requiring a skipped-state lookup hit', function () {
        $product = completableProduct(ConfigurationStatus::BUSINESS_SET);
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new CompleteConfigurationHandler($repo, new ConfigurationFlowResolver(), new ProductConfigStatusService(), $transaction);

        expect($handler->handle(new CompleteConfigurationCommand(42))->configurationStatus)->toBe(ConfigurationStatus::COMPLETED);
    });

    it('is idempotent when the product is already completed', function () {
        $product = completableProduct(ConfigurationStatus::COMPLETED);
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());
        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldNotReceive('save');

        $handler = new CompleteConfigurationHandler($repo, new ConfigurationFlowResolver(), new ProductConfigStatusService(), $transaction);

        expect($handler->handle(new CompleteConfigurationCommand(42))->configurationStatus)->toBe(ConfigurationStatus::COMPLETED);
    });

    it('rejects completion without a target url', function () {
        $product = completableProduct(ConfigurationStatus::ASSIGNED, null);
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());
        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);

        $handler = new CompleteConfigurationHandler($repo, new ConfigurationFlowResolver(), new ProductConfigStatusService(), $transaction);

        expect(fn () => $handler->handle(new CompleteConfigurationCommand(42)))->toThrow(InvalidConfigurationTransition::class);
    });
});
