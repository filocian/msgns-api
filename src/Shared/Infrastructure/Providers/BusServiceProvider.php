<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Bus\LaravelCommandBus;
use Src\Shared\Infrastructure\Bus\LaravelEventBus;
use Src\Shared\Infrastructure\Bus\LaravelQueryBus;

final class BusServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->app->singleton(CommandBus::class, LaravelCommandBus::class);
		$this->app->singleton(QueryBus::class, LaravelQueryBus::class);
		$this->app->singleton(EventBus::class, LaravelEventBus::class);
	}
}
