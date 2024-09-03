<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\Repositories\B4a\B4aRepository;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		$this->app->singleton(B4aRepository::class, function ($app) {
			return new B4aRepository();
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
