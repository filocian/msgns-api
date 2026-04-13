<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\GoogleBusiness\Application\Commands\ConnectGoogleBusiness\ConnectGoogleBusinessHandler;
use Src\GoogleBusiness\Application\Commands\DisconnectGoogleBusiness\DisconnectGoogleBusinessHandler;
use Src\GoogleBusiness\Application\Queries\GetGoogleBusinessConnection\GetGoogleBusinessConnectionHandler;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessApiPort;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessConnectionRepositoryPort;
use Src\GoogleBusiness\Infrastructure\Adapters\GoogleBusinessApiAdapter;
use Src\GoogleBusiness\Infrastructure\Adapters\GoogleBusinessConnectionRepository;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;

final class GoogleBusinessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GoogleBusinessApiPort::class, GoogleBusinessApiAdapter::class);
        $this->app->bind(GoogleBusinessConnectionRepositoryPort::class, GoogleBusinessConnectionRepository::class);
    }

    public function boot(): void
    {
        $commandBus = $this->app->make(CommandBus::class);
        $commandBus->register('google_business.connect', ConnectGoogleBusinessHandler::class);
        $commandBus->register('google_business.disconnect', DisconnectGoogleBusinessHandler::class);

        $queryBus = $this->app->make(QueryBus::class);
        $queryBus->register('google_business.get_connection', GetGoogleBusinessConnectionHandler::class);

        // Web routes: OAuth connect/callback.
        // Deviation from GHL inline pattern (routes/web.php): module self-contained via ServiceProvider.
        Route::middleware('web')
            ->group(base_path('routes/web/google-business.php'));

        // API routes: connection status and disconnect.
        Route::prefix('api/v2/google-business')
            ->middleware('api')
            ->group(base_path('routes/api/google-business.php'));
    }
}
