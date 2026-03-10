<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Providers;

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\ServiceProvider;
use Mixpanel;
use Src\Shared\Core\Ports\AnalyticsPort;
use Src\Shared\Core\Ports\LogPort;
use Src\Shared\Core\Ports\NoSqlPort;
use Src\Shared\Core\Ports\QueuePort;
use Src\Shared\Infrastructure\Analytics\MixPanelAnalytics;
use Src\Shared\Infrastructure\Log\LaravelLog;
use Src\Shared\Infrastructure\NoSql\DynamoDbAdapter;
use Src\Shared\Infrastructure\Queue\LaravelQueue;

/**
 * Registers shared infrastructure services and external adapters.
 */
final class SharedServiceProvider extends ServiceProvider
{
	/**
	 * Registers shared kernel bindings.
	 */
	public function register(): void
	{
		$this->app->singleton(DynamoDbClient::class, static function (): DynamoDbClient {
			$key = \config('services.dynamodb.key');
			$secret = \config('services.dynamodb.secret');

			return new DynamoDbClient([
				'region' => \config('services.dynamodb.region'),
				'version' => 'latest',
				'credentials' => $key && $secret
					? [
						'key' => $key,
						'secret' => $secret,
					]
					: false,
				'retries' => 0,
			]);
		});

		$this->app->singleton(Mixpanel::class, static fn (): Mixpanel => Mixpanel::getInstance(
			(string) \config('services.mixpanel.token', ''),
		));

		$this->app->bind(QueuePort::class, LaravelQueue::class);
		$this->app->bind(LogPort::class, LaravelLog::class);
		$this->app->bind(AnalyticsPort::class, MixPanelAnalytics::class);
		$this->app->bind(NoSqlPort::class, DynamoDbAdapter::class);
	}
}
