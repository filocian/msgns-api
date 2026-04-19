<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Shared\Core\Ports\CachePort;
use Src\Shared\Infrastructure\Http\ErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response;

final class AiRateLimitMiddleware
{
    public function __construct(private readonly CachePort $cache) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->getAuthIdentifier();
        $limit = (int) config('services.gemini.rate_limit_per_minute', 2);
        $windowSeconds = (int) config('services.gemini.rate_limit_window_seconds', 60);
        $windowStart = (int) (floor(time() / $windowSeconds) * $windowSeconds);
        $cacheKey = "ai:rate:{$userId}:{$windowStart}";

        $count = (int) $this->cache->get($cacheKey, 0);

		if ($count >= $limit) {
			return ErrorResponseFactory::error('ai.rate_limited', 429);
		}

        $this->cache->set($cacheKey, $count + 1, $windowSeconds * 2);

        return $next($request);
    }
}
