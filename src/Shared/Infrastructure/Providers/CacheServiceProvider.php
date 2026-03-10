<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Providers;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;
use Src\Shared\Core\Ports\CachePort;
use Src\Shared\Infrastructure\Cache\RedisCache;

final class CacheServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->app->bind(CachePort::class, function ($app) {
			/** @var CacheFactory $cache */
			$cache = $app->make(CacheFactory::class);

			return new RedisCache($cache->store('redis'));
		});
	}
}
