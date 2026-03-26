<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\SoftRemoveProduct\SoftRemoveProductCommand;
use Src\Products\Application\Commands\SoftRemoveProduct\SoftRemoveProductHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductLifecycleService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;

function makeSoftRemoveProduct(int $id = 42): Product
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
        deletedAt: null,
    );
}

describe('SoftRemoveProductHandler', function () {
    it('delegates soft delete and returns null', function () {
        $product = makeSoftRemoveProduct();

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductLifecycleService($repo);

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldNotReceive('save');
        $repo->shouldReceive('delete')->once()->with(42);

        $handler = new SoftRemoveProductHandler($repo, $service);

        expect($handler->handle(new SoftRemoveProductCommand(productId: 42)))->toBeNull();
    });

    it('throws NotFound when the product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductLifecycleService($repo);

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('save');
        $repo->shouldNotReceive('delete');

        $handler = new SoftRemoveProductHandler($repo, $service);

        expect(fn () => $handler->handle(new SoftRemoveProductCommand(productId: 999)))->toThrow(NotFound::class);
    });
});
