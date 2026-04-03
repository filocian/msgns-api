<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Domain\ValueObjects\RedirectionTarget;
use Src\Products\Infrastructure\Cache\ProductRedirectionCacheService;
use Src\Shared\Core\Ports\CachePort;

describe('ProductRedirectionCacheService', function () {
    it('stores redirection payloads forever using the product cache key', function () {
        /** @var MockInterface&CachePort $cache */
        $cache = Mockery::mock(CachePort::class);
        $service = new ProductRedirectionCacheService($cache);

        $cache->shouldReceive('setForever')->once()->with('products:redirection:42', [
            'target' => ['url' => 'https://example.com', 'type' => 'external_url'],
            'meta' => ['userId' => 7, 'productName' => 'Product', 'password' => 'secret'],
        ]);

        $service->put(42, RedirectionTarget::externalUrl('https://example.com'), [
            'userId' => 7,
            'productName' => 'Product',
            'password' => 'secret',
        ]);
    });

    it('hydrates cached payloads back into a redirection target', function () {
        /** @var MockInterface&CachePort $cache */
        $cache = Mockery::mock(CachePort::class);
        $service = new ProductRedirectionCacheService($cache);

        $cache->shouldReceive('get')->once()->with('products:redirection:42')->andReturn([
            'target' => ['url' => 'https://cached.example.com', 'type' => 'external_url'],
            'meta' => ['userId' => 9, 'productName' => 'Cached Product', 'password' => 'secret'],
        ]);

        $payload = $service->get(42);

        expect($payload)->not->toBeNull()
            ->and($payload['target']->url)->toBe('https://cached.example.com')
            ->and($payload['target']->type->value)->toBe('external_url')
            ->and($payload['meta'])->toBe([
                'userId' => 9,
                'productName' => 'Cached Product',
                'password' => 'secret',
            ]);
    });

    it('returns null for invalid cache payloads', function () {
        /** @var MockInterface&CachePort $cache */
        $cache = Mockery::mock(CachePort::class);
        $service = new ProductRedirectionCacheService($cache);

        $cache->shouldReceive('get')->times(3)->with('products:redirection:42')->andReturn(
            null,
            ['target' => ['url' => 'https://broken.example.com']],
            ['target' => ['url' => 'https://broken.example.com', 'type' => 'external_url'], 'meta' => 'invalid'],
        );

        expect($service->get(42))->toBeNull()
            ->and($service->get(42))->toBeNull()
            ->and($service->get(42))->toBeNull();
    });

    it('forgets cache entries using the same product key strategy', function () {
        /** @var MockInterface&CachePort $cache */
        $cache = Mockery::mock(CachePort::class);
        $service = new ProductRedirectionCacheService($cache);

        $cache->shouldReceive('forget')->once()->with('products:redirection:42')->andReturn(true);

        expect($service->key(42))->toBe('products:redirection:42');

        $service->forget(42);
    });
});
