<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\RestoreProduct\RestoreProductCommand;
use Src\Products\Application\Commands\RestoreProduct\RestoreProductHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductLifecycleService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;

function makeRestorableProduct(int $id = 42, ?DateTimeImmutable $deletedAt = null): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: null,
        model: 'GPT-4',
        linkedToProductId: null,
        password: 'secret',
        targetUrl: null,
        usage: 0,
        name: 'Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::notStarted(),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: $deletedAt,
    );
}

describe('RestoreProductHandler', function () {
    it('restores the product, reloads it, and returns a ProductResource', function () {
        $trashed = makeRestorableProduct(deletedAt: new DateTimeImmutable('2024-02-01T00:00:00+00:00'));
        $fresh = makeRestorableProduct(deletedAt: null);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductLifecycleService($repo);

        $repo->shouldReceive('findByIdWithTrashed')->once()->with(42)->andReturn($trashed);
        $repo->shouldReceive('restore')->once()->with(42);
        $repo->shouldReceive('findById')->once()->with(42)->andReturn($fresh);
        $repo->shouldNotReceive('save');

        $handler = new RestoreProductHandler($repo, $service);

        $result = $handler->handle(new RestoreProductCommand(productId: 42));

        expect($result->deletedAt)->toBeNull();
    });

    it('throws NotFound when the product does not exist even with trashed lookup', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductLifecycleService($repo);

        $repo->shouldReceive('findByIdWithTrashed')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('findById');
        $repo->shouldNotReceive('restore');
        $repo->shouldNotReceive('save');

        $handler = new RestoreProductHandler($repo, $service);

        expect(fn () => $handler->handle(new RestoreProductCommand(productId: 999)))->toThrow(NotFound::class);
    });
});
