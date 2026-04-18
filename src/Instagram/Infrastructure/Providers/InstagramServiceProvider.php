<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Instagram\Domain\Ports\InstagramConnectionRepositoryPort;
use Src\Instagram\Domain\Ports\InstagramGraphApiPort;
use Src\Instagram\Domain\Ports\InstagramProductConfigurationPort;
use Src\Instagram\Infrastructure\Adapters\EloquentInstagramConnectionRepository;
use Src\Instagram\Infrastructure\Adapters\EloquentInstagramProductConfiguration;
use Src\Instagram\Infrastructure\Adapters\InstagramGraphApiAdapter;

final class InstagramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InstagramGraphApiPort::class, InstagramGraphApiAdapter::class);
        $this->app->bind(InstagramConnectionRepositoryPort::class, EloquentInstagramConnectionRepository::class);
        $this->app->bind(InstagramProductConfigurationPort::class, EloquentInstagramProductConfiguration::class);
    }

    public function boot(): void
    {
        // Web routes: OAuth connect/callback (session-based).
        Route::middleware('web')
            ->group(base_path('routes/web/instagram.php'));

        // API routes: connection status and disconnect.
        Route::prefix('api/v2/instagram')
            ->middleware(['api', 'auth:stateful-api'])
            ->group(base_path('routes/api/instagram.php'));
    }
}
