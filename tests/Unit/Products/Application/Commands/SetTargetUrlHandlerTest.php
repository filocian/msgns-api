<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\SetTargetUrl\SetTargetUrlCommand;
use Src\Products\Application\Commands\SetTargetUrl\SetTargetUrlHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductConfigurationService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;

function makeTargetUrlProduct(int $id = 42): Product
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

describe('SetTargetUrlHandler', function () {
    it('sets the target url and returns a ProductResource', function () {
        $product = makeTargetUrlProduct();

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductConfigurationService();

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new SetTargetUrlHandler($repo, $service);

        $result = $handler->handle(new SetTargetUrlCommand(productId: 42, targetUrl: 'https://example.com'));

        expect($result->targetUrl)->toBe('https://example.com');
    });

    it('throws NotFound when the product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductConfigurationService();

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('save');

        $handler = new SetTargetUrlHandler($repo, $service);

        expect(fn () => $handler->handle(new SetTargetUrlCommand(productId: 999, targetUrl: 'https://example.com')))
            ->toThrow(NotFound::class);
    });
});
