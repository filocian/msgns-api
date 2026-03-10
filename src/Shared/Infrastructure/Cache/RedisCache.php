<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Cache;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Src\Shared\Core\Ports\CachePort;

final class RedisCache implements CachePort
{
	public function __construct(private readonly Repository $cache) {}

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->cache->get($key, $default);
	}

	public function set(string $key, mixed $value, int $ttl): void
	{
		$this->cache->put($key, $value, $ttl);
	}

	/**
	 * @template TCacheValue
	 * @param Closure(): TCacheValue $callback
	 * @return TCacheValue
	 */
	public function remember(string $key, int $ttl, Closure $callback): mixed
	{
		return $this->cache->remember($key, $ttl, $callback);
	}

	public function forget(string $key): bool
	{
		return $this->cache->forget($key);
	}
}
