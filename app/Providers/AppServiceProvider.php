<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\Repositories\DynamoDb\DynamoDbRepository;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		$this->app->singleton(DynamoDbRepository::class, function ($app) {
			return new DynamoDbRepository();
		});
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		//
	}
}
