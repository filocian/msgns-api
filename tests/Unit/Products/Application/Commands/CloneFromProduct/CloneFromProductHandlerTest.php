<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\CloneFromProduct\CloneFromProductCommand;
use Src\Products\Application\Commands\CloneFromProduct\CloneFromProductHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductCloneService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

function makeCloneProduct(
    int $id,
    int $typeId,
    string $status = ConfigurationStatus::ASSIGNED,
    ?string $targetUrl = null,
): Product {
    return Product::fromPersistence(
        id: $id,
        productTypeId: $typeId,
        userId: 7,
        model: 'ModelA',
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

describe('CloneFromProductHandler', function () {
    it('clones source configuration into target', function () {
        $target = makeCloneProduct(id: 42, typeId: 5, status: ConfigurationStatus::ASSIGNED, targetUrl: 'https://old.example.com');
        $source = makeCloneProduct(id: 84, typeId: 5, status: ConfigurationStatus::BUSINESS_SET, targetUrl: 'https://new.example.com');

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($target);
        $repo->shouldReceive('findById')->once()->with(84)->andReturn($source);
        $repo->shouldReceive('save')->once()->with($target)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new CloneFromProductHandler($repo, new ProductCloneService(), $transaction);

        $result = $handler->handle(new CloneFromProductCommand(targetId: 42, sourceId: 84));

        expect($result->targetUrl)->toBe('https://new.example.com')
            ->and($result->configurationStatus)->toBe(ConfigurationStatus::BUSINESS_SET);
    });

    it('throws products_must_have_same_type when products have different types', function () {
        $target = makeCloneProduct(id: 42, typeId: 5);
        $source = makeCloneProduct(id: 84, typeId: 9);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($target);
        $repo->shouldReceive('findById')->once()->with(84)->andReturn($source);
        $repo->shouldNotReceive('save');

        $handler = new CloneFromProductHandler($repo, new ProductCloneService(), $transaction);

        expect(fn () => $handler->handle(new CloneFromProductCommand(targetId: 42, sourceId: 84)))
            ->toThrow(ValidationFailed::class, 'products_must_have_same_type');
    });

    it('throws NotFound when target product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn(null);
        $repo->shouldNotReceive('save');

        $handler = new CloneFromProductHandler($repo, new ProductCloneService(), $transaction);

        expect(fn () => $handler->handle(new CloneFromProductCommand(targetId: 42, sourceId: 84)))
            ->toThrow(NotFound::class);
    });

    it('throws NotFound when source product does not exist', function () {
        $target = makeCloneProduct(id: 42, typeId: 5);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($target);
        $repo->shouldReceive('findById')->once()->with(84)->andReturn(null);
        $repo->shouldNotReceive('save');

        $handler = new CloneFromProductHandler($repo, new ProductCloneService(), $transaction);

        expect(fn () => $handler->handle(new CloneFromProductCommand(targetId: 42, sourceId: 84)))
            ->toThrow(NotFound::class);
    });
});
