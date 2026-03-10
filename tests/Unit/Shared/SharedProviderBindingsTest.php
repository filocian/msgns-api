<?php

declare(strict_types=1);

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Psr\Log\LoggerInterface;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Core\Ports\AnalyticsPort;
use Src\Shared\Core\Ports\CachePort;
use Src\Shared\Core\Ports\LogPort;
use Src\Shared\Core\Ports\NoSqlPort;
use Src\Shared\Core\Ports\QueuePort;
use Src\Shared\Infrastructure\Analytics\MixPanelAnalytics;
use Src\Shared\Infrastructure\Bus\LaravelCommandBus;
use Src\Shared\Infrastructure\Bus\LaravelEventBus;
use Src\Shared\Infrastructure\Bus\LaravelQueryBus;
use Src\Shared\Infrastructure\Cache\RedisCache;
use Src\Shared\Infrastructure\Log\LaravelLog;
use Src\Shared\Infrastructure\NoSql\DynamoDbAdapter;
use Src\Shared\Infrastructure\Queue\LaravelQueue;

describe('Shared service provider bindings', function () {
	it('resolves shared bus contracts to laravel implementations', function () {
		expect(app(CommandBus::class))->toBeInstanceOf(LaravelCommandBus::class)
			->and(app(QueryBus::class))->toBeInstanceOf(LaravelQueryBus::class)
			->and(app(EventBus::class))->toBeInstanceOf(LaravelEventBus::class);
	});

	it('resolves shared infrastructure ports from the container', function () {
		$cacheRepository = \Mockery::mock(Repository::class);
		$cacheFactory = \Mockery::mock(CacheFactory::class);
		$cacheFactory->shouldReceive('store')->once()->with('redis')->andReturn($cacheRepository);

		app()->instance(CacheFactory::class, $cacheFactory);
		app()->instance(QueueContract::class, \Mockery::mock(QueueContract::class));
		app()->instance(LoggerInterface::class, \Mockery::mock(LoggerInterface::class));
		app()->instance(AuthFactory::class, sharedAuthFactoryWithUser(null));
		app()->instance(Mixpanel::class, \Mockery::mock(Mixpanel::class));
		app()->instance(DynamoDbClient::class, \Mockery::mock(DynamoDbClient::class));

		expect(app(CachePort::class))->toBeInstanceOf(RedisCache::class)
			->and(app(QueuePort::class))->toBeInstanceOf(LaravelQueue::class)
			->and(app(LogPort::class))->toBeInstanceOf(LaravelLog::class)
			->and(app(AnalyticsPort::class))->toBeInstanceOf(MixPanelAnalytics::class)
			->and(app(NoSqlPort::class))->toBeInstanceOf(DynamoDbAdapter::class);
	});
});

function sharedAuthFactoryWithUser(?Authenticatable $user): AuthFactory
{
	$guard = \Mockery::mock(Guard::class);
	$guard->shouldReceive('user')->andReturn($user);

	$factory = \Mockery::mock(AuthFactory::class);
	$factory->shouldReceive('guard')->andReturn($guard);

	return $factory;
}
