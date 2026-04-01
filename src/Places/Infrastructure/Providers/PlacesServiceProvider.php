<?php

declare(strict_types=1);

namespace Src\Places\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Places\Application\Queries\SearchPlace\SearchPlaceHandler;
use Src\Places\Domain\Ports\GooglePlacesPort;
use Src\Places\Infrastructure\Adapters\GooglePlacesAdapter;
use Src\Shared\Core\Bus\QueryBus;

final class PlacesServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->app->bind(GooglePlacesPort::class, GooglePlacesAdapter::class);
	}

	public function boot(): void
	{
		$queryBus = $this->app->make(QueryBus::class);
		$queryBus->register('places.search_place', SearchPlaceHandler::class);

		Route::prefix('api/v2/places')
			->middleware('api')
			->group(base_path('routes/api/places.php'));
	}
}
