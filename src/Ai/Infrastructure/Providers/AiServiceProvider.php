<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Ai\Application\Commands\CancelClassicSubscription\CancelClassicSubscriptionHandler;
use Src\Ai\Application\Commands\DeleteUserSystemPrompt\DeleteUserSystemPromptHandler;
use Src\Ai\Application\Commands\PurchasePrepaidPackage\PurchasePrepaidPackageHandler;
use Src\Ai\Application\Commands\SubscribeToClassicPlan\SubscribeToClassicPlanHandler;
use Src\Ai\Application\Commands\UpsertUserSystemPrompt\UpsertUserSystemPromptHandler;
use Src\Ai\Application\Queries\GetActiveClassicSubscription\GetActiveClassicSubscriptionHandler;
use Src\Ai\Application\Queries\GetPrepaidBalances\GetPrepaidBalancesHandler;
use Src\Ai\Application\Queries\GetPrepaidPackages\GetPrepaidPackagesHandler;
use Src\Ai\Application\Queries\GetUserSystemPrompts\GetUserSystemPromptsHandler;
use Src\Ai\Domain\Ports\ClassicSubscriptionBrokerPort;
use Src\Ai\Domain\Ports\GeminiPort;
use Src\Ai\Domain\Ports\PrepaidChargePort;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Ai\Infrastructure\Adapters\CashierPrepaidChargeAdapter;
use Src\Ai\Infrastructure\Adapters\CashierSubscriptionAdapter;
use Src\Ai\Infrastructure\Adapters\GeminiApiAdapter;
use Src\Ai\Application\Commands\ApplyAiResponse\ApplyAiResponseHandler;
use Src\Ai\Application\Commands\ApproveAiResponse\ApproveAiResponseHandler;
use Src\Ai\Application\Commands\EditAiResponse\EditAiResponseHandler;
use Src\Ai\Application\Commands\RejectAiResponse\RejectAiResponseHandler;
use Src\Ai\Application\Queries\ListAiResponses\ListAiResponsesHandler;
use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\Services\CompositeAiResponseApplier;
use Src\Ai\Infrastructure\Console\Commands\ResetExpiredAiResponsesCommand;
use Src\Ai\Infrastructure\Console\Commands\ResetFreeAiUsageCommand;
use Src\Ai\Infrastructure\Http\Middleware\AiRateLimitMiddleware;
use Src\Ai\Infrastructure\Listeners\AssignFreeAiPermissionListener;
use Src\Ai\Infrastructure\Persistence\EloquentUserAiSystemPromptRepository;
use Src\Identity\Domain\Events\UserActivated;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;

final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GeminiPort::class, GeminiApiAdapter::class);
        $this->app->bind(UserAiSystemPromptRepository::class, EloquentUserAiSystemPromptRepository::class);
        $this->app->bind(ClassicSubscriptionBrokerPort::class, CashierSubscriptionAdapter::class);
        $this->app->bind(PrepaidChargePort::class, CashierPrepaidChargeAdapter::class);

        $this->app->bind(AiResponseApplierPort::class, function (): CompositeAiResponseApplier {
            return new CompositeAiResponseApplier([
                // BE-12 and BE-13 will add implementations here
            ]);
        });
    }

    public function boot(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('ai.rate-limit', AiRateLimitMiddleware::class);

        $this->app->make(QueryBus::class)->register('ai.get_user_system_prompts', GetUserSystemPromptsHandler::class);
        $this->app->make(QueryBus::class)->register('ai.get_active_classic_subscription', GetActiveClassicSubscriptionHandler::class);
        $this->app->make(QueryBus::class)->register('ai.list_ai_responses', ListAiResponsesHandler::class);
        $this->app->make(CommandBus::class)->register('ai.upsert_user_system_prompt', UpsertUserSystemPromptHandler::class);
        $this->app->make(CommandBus::class)->register('ai.delete_user_system_prompt', DeleteUserSystemPromptHandler::class);
        $this->app->make(CommandBus::class)->register('ai.subscribe_to_classic_plan', SubscribeToClassicPlanHandler::class);
        $this->app->make(CommandBus::class)->register('ai.cancel_classic_subscription', CancelClassicSubscriptionHandler::class);
        $this->app->make(CommandBus::class)->register('ai.approve_ai_response', ApproveAiResponseHandler::class);
        $this->app->make(CommandBus::class)->register('ai.edit_ai_response', EditAiResponseHandler::class);
        $this->app->make(CommandBus::class)->register('ai.reject_ai_response', RejectAiResponseHandler::class);
        $this->app->make(CommandBus::class)->register('ai.apply_ai_response', ApplyAiResponseHandler::class);
        $this->app->make(CommandBus::class)->register('ai.purchase_prepaid_package', PurchasePrepaidPackageHandler::class);
        $this->app->make(QueryBus::class)->register('ai.get_prepaid_balances', GetPrepaidBalancesHandler::class);
        $this->app->make(QueryBus::class)->register('ai.get_prepaid_packages', GetPrepaidPackagesHandler::class);

        Route::prefix('api/v2/ai')
            ->middleware('api')
            ->group(base_path('routes/api/ai.php'));

        Event::listen(UserActivated::class, AssignFreeAiPermissionListener::class);

        $this->commands([ResetFreeAiUsageCommand::class, ResetExpiredAiResponsesCommand::class]);
    }
}
