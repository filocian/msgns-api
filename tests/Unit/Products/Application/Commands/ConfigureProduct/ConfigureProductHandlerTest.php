<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\ConfigureProduct\ConfigureProductCommand;
use Src\Products\Application\Commands\ConfigureProduct\ConfigureProductHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ConfigurationFlowResolver;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\Services\ProductConfigurationService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

function makeConfigurableProduct(int $id = 42, string $status = ConfigurationStatus::ASSIGNED, string $model = 'google'): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: 7,
        model: $model,
        linkedToProductId: null,
        password: 'secret',
        targetUrl: null,
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

describe('ConfigureProductHandler', function () {
    it('sets target url and auto-advances assigned simple products to completed', function () {
        $product = makeConfigurableProduct(status: ConfigurationStatus::ASSIGNED);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new ConfigureProductHandler(
            $repo,
            new ProductConfigurationService(),
            new ProductConfigStatusService(),
            new ConfigurationFlowResolver(),
            $transaction,
        );

        $result = $handler->handle(new ConfigureProductCommand(productId: 42, targetUrl: 'https://example.com'));

        expect($result->targetUrl)->toBe('https://example.com')
            ->and($result->configurationStatus)->toBe(ConfigurationStatus::COMPLETED);
    });

    it('keeps advanced status when already beyond target-set', function () {
        $product = makeConfigurableProduct(status: ConfigurationStatus::BUSINESS_SET);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new ConfigureProductHandler(
            $repo,
            new ProductConfigurationService(),
            new ProductConfigStatusService(),
            new ConfigurationFlowResolver(),
            $transaction,
        );

        $result = $handler->handle(new ConfigureProductCommand(productId: 42, targetUrl: 'https://example.com/new'));

        expect($result->targetUrl)->toBe('https://example.com/new')
            ->and($result->configurationStatus)->toBe(ConfigurationStatus::BUSINESS_SET);
    });

    it('throws NotFound when product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('save');

        $handler = new ConfigureProductHandler(
            $repo,
            new ProductConfigurationService(),
            new ProductConfigStatusService(),
            new ConfigurationFlowResolver(),
            $transaction,
        );

        expect(fn () => $handler->handle(new ConfigureProductCommand(productId: 999, targetUrl: 'https://example.com')))
            ->toThrow(NotFound::class);
    });
});
