<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\DeactivateProduct\DeactivateProductCommand;
use Src\Products\Application\Commands\DeactivateProduct\DeactivateProductHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductActivationService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;

function makeDeactivatableProduct(int $id = 42, bool $active = true): Product
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
        active: $active,
        configurationStatus: ConfigurationStatus::notStarted(),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('DeactivateProductHandler', function () {
    it('deactivates the product and returns a ProductResource', function () {
        $product = makeDeactivatableProduct(active: true);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductActivationService();

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new DeactivateProductHandler($repo, $service);

        $result = $handler->handle(new DeactivateProductCommand(productId: 42));

        expect($result->active)->toBeFalse();
    });

    it('throws NotFound when the product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductActivationService();

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('save');

        $handler = new DeactivateProductHandler($repo, $service);

        expect(fn () => $handler->handle(new DeactivateProductCommand(productId: 999)))->toThrow(NotFound::class);
    });

    it('returns the current product resource when the product is already inactive', function () {
        $product = makeDeactivatableProduct(active: false);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductActivationService();

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new DeactivateProductHandler($repo, $service);

        $result = $handler->handle(new DeactivateProductCommand(productId: 42));

        expect($result->active)->toBeFalse();
    });
});
