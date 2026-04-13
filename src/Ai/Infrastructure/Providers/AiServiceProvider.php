<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Ai\Application\Commands\DeleteUserSystemPrompt\DeleteUserSystemPromptHandler;
use Src\Ai\Application\Commands\UpsertUserSystemPrompt\UpsertUserSystemPromptHandler;
use Src\Ai\Application\Queries\GetUserSystemPrompts\GetUserSystemPromptsHandler;
use Src\Ai\Domain\Ports\GeminiPort;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Ai\Infrastructure\Adapters\GeminiApiAdapter;
use Src\Ai\Infrastructure\Http\Middleware\AiRateLimitMiddleware;
use Src\Ai\Infrastructure\Persistence\EloquentUserAiSystemPromptRepository;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;

final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GeminiPort::class, GeminiApiAdapter::class);
        $this->app->bind(UserAiSystemPromptRepository::class, EloquentUserAiSystemPromptRepository::class);
    }

    public function boot(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('ai.rate-limit', AiRateLimitMiddleware::class);

        $this->app->make(QueryBus::class)->register('ai.get_user_system_prompts', GetUserSystemPromptsHandler::class);
        $this->app->make(CommandBus::class)->register('ai.upsert_user_system_prompt', UpsertUserSystemPromptHandler::class);
        $this->app->make(CommandBus::class)->register('ai.delete_user_system_prompt', DeleteUserSystemPromptHandler::class);

        Route::prefix('api/v2/ai')
            ->middleware('api')
            ->group(base_path('routes/api/ai.php'));
    }
}
