<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Ai\Domain\Ports\GeminiPort;
use Src\Ai\Infrastructure\Adapters\GeminiApiAdapter;
use Src\Ai\Infrastructure\Http\Middleware\AiRateLimitMiddleware;

final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GeminiPort::class, GeminiApiAdapter::class);
    }

    public function boot(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('ai.rate-limit', AiRateLimitMiddleware::class);

        Route::prefix('api/v2/ai')
            ->middleware('api')
            ->group(base_path('routes/api/ai.php'));
    }
}
