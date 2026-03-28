<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\Repositories\DynamoDb\DynamoDbRepository;
use Illuminate\Support\ServiceProvider;
use L5Swagger\L5SwaggerServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		if ($this->app->environment(['local', 'testing', 'staging']) || env('SWAGGER_ENABLE')) {
			$this->app->register(L5SwaggerServiceProvider::class);
		}

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
