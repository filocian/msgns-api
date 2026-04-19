<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Mockery\MockInterface;
use Src\Ai\Infrastructure\Http\Middleware\AiRateLimitMiddleware;
use Src\Shared\Core\Ports\CachePort;

afterEach(fn () => Mockery::close());

describe('AiRateLimitMiddleware', function () {
    beforeEach(function () {
        config()->set('services.gemini.rate_limit_per_minute', 2);
        config()->set('services.gemini.rate_limit_window_seconds', 60);
    });

    it('passes the request through and sets the counter to 1 on first request', function () {
        $userId      = 42;
        $windowStart = (int) (floor(time() / 60) * 60);
        $cacheKey    = "ai:rate:{$userId}:{$windowStart}";

        /** @var MockInterface&CachePort $cache */
        $cache = Mockery::mock(CachePort::class);
        $cache->expects('get')->with($cacheKey, 0)->andReturn(0);
        $cache->expects('set')->with($cacheKey, 1, 120);

        $user = Mockery::mock();
        $user->shouldReceive('getAuthIdentifier')->andReturn($userId);

        /** @var MockInterface&Request $request */
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);

        $next = fn ($req) => new \Illuminate\Http\Response('ok', 200);

        $middleware = new AiRateLimitMiddleware($cache);
        $response   = $middleware->handle($request, $next);

        expect($response->getStatusCode())->toBe(200);
    });

    it('allows requests below the limit and increments the counter', function () {
        $userId      = 7;
        $windowStart = (int) (floor(time() / 60) * 60);
        $cacheKey    = "ai:rate:{$userId}:{$windowStart}";

        /** @var MockInterface&CachePort $cache */
        $cache = Mockery::mock(CachePort::class);
        $cache->expects('get')->with($cacheKey, 0)->andReturn(1);
        $cache->expects('set')->with($cacheKey, 2, 120);

        $user = Mockery::mock();
        $user->shouldReceive('getAuthIdentifier')->andReturn($userId);

        /** @var MockInterface&Request $request */
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);

        $next = fn ($req) => new \Illuminate\Http\Response('ok', 200);

        $middleware = new AiRateLimitMiddleware($cache);
        $response   = $middleware->handle($request, $next);

        expect($response->getStatusCode())->toBe(200);
    });

    it('returns 429 json when the limit is exactly reached', function () {
        $userId      = 42;
        $windowStart = (int) (floor(time() / 60) * 60);
        $cacheKey    = "ai:rate:{$userId}:{$windowStart}";

        /** @var MockInterface&CachePort $cache */
        $cache = Mockery::mock(CachePort::class);
        $cache->expects('get')->with($cacheKey, 0)->andReturn(2);

        $user = Mockery::mock();
        $user->shouldReceive('getAuthIdentifier')->andReturn($userId);

        /** @var MockInterface&Request $request */
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);

        $next = fn ($req) => new \Illuminate\Http\Response('ok', 200);

        $middleware = new AiRateLimitMiddleware($cache);
        $response   = $middleware->handle($request, $next);

        expect($response->getStatusCode())->toBe(429)
            ->and(json_decode((string) $response->getContent(), true))->toBe([
                'error' => [
                    'code' => 'ai.rate_limited',
                    'context' => [],
                ],
            ]);
    });

    it('does not call set when the request is rejected', function () {
        $userId      = 42;
        $windowStart = (int) (floor(time() / 60) * 60);
        $cacheKey    = "ai:rate:{$userId}:{$windowStart}";

        /** @var MockInterface&CachePort $cache */
        $cache = Mockery::mock(CachePort::class);
        $cache->expects('get')->with($cacheKey, 0)->andReturn(5);
        $cache->shouldNotReceive('set');

        $user = Mockery::mock();
        $user->shouldReceive('getAuthIdentifier')->andReturn($userId);

        /** @var MockInterface&Request $request */
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);

        $next = fn ($req) => new \Illuminate\Http\Response('ok', 200);

        $middleware = new AiRateLimitMiddleware($cache);
        $middleware->handle($request, $next);
    });

    it('uses a separate cache key per user', function () {
        $windowStart  = (int) (floor(time() / 60) * 60);
        $cacheKeyUser1 = "ai:rate:1:{$windowStart}";
        $cacheKeyUser2 = "ai:rate:2:{$windowStart}";

        /** @var MockInterface&CachePort $cache */
        $cache = Mockery::mock(CachePort::class);
        $cache->expects('get')->with($cacheKeyUser1, 0)->andReturn(0);
        $cache->expects('set')->with($cacheKeyUser1, 1, 120);

        $user1 = Mockery::mock();
        $user1->shouldReceive('getAuthIdentifier')->andReturn(1);

        /** @var MockInterface&Request $request */
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user1);

        $next = fn ($req) => new \Illuminate\Http\Response('ok', 200);

        $middleware = new AiRateLimitMiddleware($cache);
        $middleware->handle($request, $next);

        // Now user 2 — fresh counter, different key
        $cache->expects('get')->with($cacheKeyUser2, 0)->andReturn(0);
        $cache->expects('set')->with($cacheKeyUser2, 1, 120);

        $user2 = Mockery::mock();
        $user2->shouldReceive('getAuthIdentifier')->andReturn(2);

        $request2 = Mockery::mock(Request::class);
        $request2->shouldReceive('user')->andReturn($user2);

        $middleware->handle($request2, $next);
    });

    it('uses ttl of window seconds times two to survive full window', function () {
        config()->set('services.gemini.rate_limit_window_seconds', 120);

        $userId      = 10;
        $windowStart = (int) (floor(time() / 120) * 120);
        $cacheKey    = "ai:rate:{$userId}:{$windowStart}";

        /** @var MockInterface&CachePort $cache */
        $cache = Mockery::mock(CachePort::class);
        $cache->expects('get')->with($cacheKey, 0)->andReturn(0);
        $cache->expects('set')->with($cacheKey, 1, 240); // 120 * 2

        $user = Mockery::mock();
        $user->shouldReceive('getAuthIdentifier')->andReturn($userId);

        /** @var MockInterface&Request $request */
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);

        $next = fn ($req) => new \Illuminate\Http\Response('ok', 200);

        $middleware = new AiRateLimitMiddleware($cache);
        $response   = $middleware->handle($request, $next);

        expect($response->getStatusCode())->toBe(200);
    });
});
