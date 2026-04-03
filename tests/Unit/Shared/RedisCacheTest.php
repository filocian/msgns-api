<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Repository;
use Src\Shared\Infrastructure\Cache\RedisCache;

describe('RedisCache', function () {
	it('delegates get and set operations to the cache repository', function () {
		$repository = \Mockery::mock(Repository::class);
		$repository->shouldReceive('put')->once()->with('shared:key', ['name' => 'Widget'], 60);
		$repository->shouldReceive('get')->once()->with('shared:key', null)->andReturn(['name' => 'Widget']);

		$cache = new RedisCache($repository);
		$cache->set('shared:key', ['name' => 'Widget'], 60);

		expect($cache->get('shared:key'))->toBe(['name' => 'Widget']);
	});

	it('delegates remember callbacks and forget operations', function () {
		$repository = \Mockery::mock(Repository::class);
		$repository->shouldReceive('remember')->once()->withArgs(function (string $key, int $ttl, Closure $callback) {
			return $key === 'shared:remembered'
				&& $ttl === 120
				&& $callback() === ['fresh' => true];
		})->andReturn(['fresh' => true]);
		$repository->shouldReceive('forget')->once()->with('shared:remembered')->andReturn(true);

		$cache = new RedisCache($repository);

		expect($cache->remember('shared:remembered', 120, fn () => ['fresh' => true]))->toBe(['fresh' => true])
			->and($cache->forget('shared:remembered'))->toBeTrue();
	});

	it('stores values forever through the cache repository', function () {
		$repository = \Mockery::mock(Repository::class);
		$repository->shouldReceive('forever')->once()->with('shared:forever', ['cached' => true]);

		$cache = new RedisCache($repository);
		$cache->setForever('shared:forever', ['cached' => true]);
	});
});
