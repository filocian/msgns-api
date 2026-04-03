<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Queries\ResolveProductRedirection\ResolveProductRedirectionHandler;
use Src\Products\Application\Queries\ResolveProductRedirection\ResolveProductRedirectionQuery;
use Src\Products\Domain\Contracts\ProductRedirectionStrategy;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductScanned;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\RedirectionTarget;
use Src\Products\Infrastructure\Cache\ProductRedirectionCacheService;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\CachePort;

function redirectionHandlerProduct(int $id = 42, string $model = 'google', ?string $targetUrl = 'https://example.com', bool $active = true, string $status = ConfigurationStatus::COMPLETED): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: 9,
        model: $model,
        linkedToProductId: null,
        password: 'secret',
        targetUrl: $targetUrl,
        usage: 0,
        name: 'Product Name',
        description: null,
        active: $active,
        configurationStatus: ConfigurationStatus::from($status),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('ResolveProductRedirectionHandler', function () {
    it('returns cached targets without hitting the repository', function () {
        /** @var MockInterface&ProductRepositoryPort $repository */
        $repository = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductRedirectionStrategy $strategy */
        $strategy = Mockery::mock(ProductRedirectionStrategy::class);
        /** @var MockInterface&ProductUsagePort $usage */
        $usage = Mockery::mock(ProductUsagePort::class);
        /** @var MockInterface&EventBus $eventBus */
        $eventBus = Mockery::mock(EventBus::class);
        /** @var MockInterface&CachePort $cachePort */
        $cachePort = Mockery::mock(CachePort::class);
        $cache = new ProductRedirectionCacheService($cachePort);

        $repository->shouldNotReceive('findByIdAndPassword');
        $strategy->shouldNotReceive('supports');
        $cachePort->shouldReceive('get')->once()->with('products:redirection:42')->andReturn([
            'target' => ['url' => 'https://cached.com', 'type' => 'external_url'],
            'meta' => ['userId' => 9, 'productName' => 'Cached', 'password' => 'secret'],
        ]);
        $usage->shouldReceive('writeUsageEvent')->once();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(ProductScanned::class));

        $handler = new ResolveProductRedirectionHandler($repository, $strategy, $usage, $eventBus, $cache);

        expect($handler->handle(new ResolveProductRedirectionQuery(42, 'secret', 'en'))->url)->toBe('https://cached.com');
    });

    it('falls back to the database when cached password does not match', function () {
        /** @var MockInterface&ProductRepositoryPort $repository */
        $repository = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductRedirectionStrategy $strategy */
        $strategy = Mockery::mock(ProductRedirectionStrategy::class);
        /** @var MockInterface&ProductUsagePort $usage */
        $usage = Mockery::mock(ProductUsagePort::class);
        /** @var MockInterface&EventBus $eventBus */
        $eventBus = Mockery::mock(EventBus::class);
        /** @var MockInterface&CachePort $cachePort */
        $cachePort = Mockery::mock(CachePort::class);
        $cache = new ProductRedirectionCacheService($cachePort);

        $cachePort->shouldReceive('get')->once()->with('products:redirection:42')->andReturn([
            'target' => ['url' => 'https://cached.com', 'type' => 'external_url'],
            'meta' => ['userId' => 9, 'productName' => 'Cached', 'password' => 'other'],
        ]);
        $repository->shouldReceive('findByIdAndPassword')->once()->with(42, 'secret')->andReturn(null);

        $handler = new ResolveProductRedirectionHandler($repository, $strategy, $usage, $eventBus, $cache);

        expect(fn () => $handler->handle(new ResolveProductRedirectionQuery(42, 'secret', 'en')))->toThrow(NotFound::class);
    });

    it('stores successful database resolutions in cache and publishes the scan event', function () {
        $product = redirectionHandlerProduct();
        /** @var MockInterface&ProductRepositoryPort $repository */
        $repository = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductRedirectionStrategy $strategy */
        $strategy = Mockery::mock(ProductRedirectionStrategy::class);
        /** @var MockInterface&ProductUsagePort $usage */
        $usage = Mockery::mock(ProductUsagePort::class);
        /** @var MockInterface&EventBus $eventBus */
        $eventBus = Mockery::mock(EventBus::class);
        /** @var MockInterface&CachePort $cachePort */
        $cachePort = Mockery::mock(CachePort::class);
        $cache = new ProductRedirectionCacheService($cachePort);

        $cachePort->shouldReceive('get')->once()->with('products:redirection:42')->andReturn(null);
        $repository->shouldReceive('findByIdAndPassword')->once()->andReturn($product);
        $strategy->shouldReceive('supports')->once()->with($product)->andReturn(true);
        $strategy->shouldReceive('resolve')->once()->andReturn(RedirectionTarget::externalUrl('https://resolved.com'));
        $cachePort->shouldReceive('setForever')->once()->with('products:redirection:42', [
            'target' => ['url' => 'https://resolved.com', 'type' => 'external_url'],
            'meta' => ['userId' => 9, 'productName' => 'Product Name', 'password' => 'secret'],
        ]);
        $usage->shouldReceive('writeUsageEvent')->once();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(ProductScanned::class));

        $handler = new ResolveProductRedirectionHandler($repository, $strategy, $usage, $eventBus, $cache);

        expect($handler->handle(new ResolveProductRedirectionQuery(42, 'secret', 'en'))->url)->toBe('https://resolved.com');
    });
});
