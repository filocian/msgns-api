<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Src\Products\Domain\Events\ProductActivated;
use Src\Products\Domain\Events\ProductAssigned;
use Src\Products\Domain\Events\ProductBusinessUpdated;
use Src\Products\Domain\Events\ProductConfigStatusChanged;
use Src\Products\Domain\Events\ProductConfigurationCompleted;
use Src\Products\Domain\Events\ProductDeactivated;
use Src\Products\Domain\Events\ProductRenamed;
use Src\Products\Domain\Events\ProductRestored;
use Src\Products\Domain\Events\ProductReset;
use Src\Products\Domain\Events\ProductSoftDeleted;
use Src\Products\Domain\Events\ProductTargetUrlSet;
use Src\Products\Domain\Events\ProductUnlinked;
use Src\Products\Domain\Events\ProductsPaired;
use Src\Products\Infrastructure\Cache\ProductRedirectionCacheService;
use Src\Products\Infrastructure\Listeners\InvalidateProductRedirectionCache;

describe('InvalidateProductRedirectionCache', function () {
    it('forgets cache for every single-product mutation event', function (object $event) {
        /** @var MockInterface&\Src\Shared\Core\Ports\CachePort $cache */
        $cache = Mockery::mock(\Src\Shared\Core\Ports\CachePort::class);
        $cache->shouldReceive('forget')->once()->with('products:redirection:42')->andReturn(true);

        $cacheService = new ProductRedirectionCacheService($cache);

        $listener = new InvalidateProductRedirectionCache($cacheService);

        $listener->handle($event);
    })->with([
        new ProductTargetUrlSet(42, 'https://example.com'),
        new ProductActivated(42),
        new ProductDeactivated(42),
        new ProductReset(42),
        new ProductAssigned(42, 7),
        new ProductBusinessUpdated(42, ['company' => 'Acme']),
        new ProductRenamed(42, 'Renamed Product'),
        new ProductConfigurationCompleted(42, 'google', new DateTimeImmutable('2024-01-01T00:00:00+00:00')),
        new ProductConfigStatusChanged(42, 'assigned', 'completed'),
        new ProductSoftDeleted(42),
        new ProductRestored(42),
        new ProductUnlinked(42, 77),
    ]);

    it('forgets cache for both products in a pairing event', function () {
        /** @var MockInterface&\Src\Shared\Core\Ports\CachePort $cache */
        $cache = Mockery::mock(\Src\Shared\Core\Ports\CachePort::class);
        $cache->shouldReceive('forget')->once()->with('products:redirection:10')->andReturn(true);
        $cache->shouldReceive('forget')->once()->with('products:redirection:20')->andReturn(true);

        $cacheService = new ProductRedirectionCacheService($cache);

        $listener = new InvalidateProductRedirectionCache($cacheService);

        $listener->handle(new ProductsPaired(10, 20));
    });

    it('logs and swallows cache invalidation errors', function () {
        /** @var MockInterface&\Src\Shared\Core\Ports\CachePort $cache */
        $cache = Mockery::mock(\Src\Shared\Core\Ports\CachePort::class);
        $cache->shouldReceive('forget')->once()->with('products:redirection:42')->andThrow(new RuntimeException('redis down'));
        $cacheService = new ProductRedirectionCacheService($cache);
        Log::shouldReceive('warning')->once()->with('Failed to invalidate product redirection cache', Mockery::on(
            static fn (array $context): bool => $context['product_id'] === 42
                && $context['event'] === ProductSoftDeleted::class
                && $context['error'] === 'redis down'
        ));

        $listener = new InvalidateProductRedirectionCache($cacheService);

        $listener->handle(new ProductSoftDeleted(42));
    });
});
