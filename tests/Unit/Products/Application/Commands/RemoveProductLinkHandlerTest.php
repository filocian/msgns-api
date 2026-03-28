<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\RemoveProductLink\RemoveProductLinkCommand;
use Src\Products\Application\Commands\RemoveProductLink\RemoveProductLinkHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductGroupingService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;

function makeLinkedProduct(int $id = 42): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: null,
        model: 'GPT-4',
        linkedToProductId: 77,
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

describe('RemoveProductLinkHandler', function () {
    it('removes the product link and returns a ProductResource', function () {
        $product = makeLinkedProduct();

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductGroupingService();

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new RemoveProductLinkHandler($repo, $service);

        $result = $handler->handle(new RemoveProductLinkCommand(productId: 42));

        expect($result->linkedToProductId)->toBeNull();
    });

    it('throws NotFound when the product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductGroupingService();

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('save');

        $handler = new RemoveProductLinkHandler($repo, $service);

        expect(fn () => $handler->handle(new RemoveProductLinkCommand(productId: 999)))->toThrow(NotFound::class);
    });
});
