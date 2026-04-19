<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Subscriptions\Application\Commands\CreateSubscriptionType\CreateSubscriptionTypeHandler;
use Src\Subscriptions\Application\Commands\DeleteSubscriptionType\DeleteSubscriptionTypeHandler;
use Src\Subscriptions\Application\Commands\ToggleSubscriptionTypeActive\ToggleSubscriptionTypeActiveHandler;
use Src\Subscriptions\Application\Commands\UpdateSubscriptionType\UpdateSubscriptionTypeHandler;
use Src\Subscriptions\Application\Queries\GetSubscriptionType\GetSubscriptionTypeHandler;
use Src\Subscriptions\Application\Queries\ListAdminSubscriptionTypes\ListAdminSubscriptionTypesHandler;
use Src\Subscriptions\Application\Queries\ListPublicSubscriptionTypes\ListPublicSubscriptionTypesHandler;
use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;
use Src\Subscriptions\Infrastructure\Persistence\EloquentSubscriptionTypeRepository;

final class SubscriptionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubscriptionTypeRepositoryPort::class, EloquentSubscriptionTypeRepository::class);
    }

    public function boot(): void
    {
        $commandBus = $this->app->make(CommandBus::class);
        $commandBus->register('subscriptions.create_subscription_type', CreateSubscriptionTypeHandler::class);
        $commandBus->register('subscriptions.update_subscription_type', UpdateSubscriptionTypeHandler::class);
        $commandBus->register('subscriptions.toggle_subscription_type_active', ToggleSubscriptionTypeActiveHandler::class);
        $commandBus->register('subscriptions.delete_subscription_type', DeleteSubscriptionTypeHandler::class);

        $queryBus = $this->app->make(QueryBus::class);
        $queryBus->register('subscriptions.list_admin_subscription_types', ListAdminSubscriptionTypesHandler::class);
        $queryBus->register('subscriptions.get_subscription_type', GetSubscriptionTypeHandler::class);
        $queryBus->register('subscriptions.list_public_subscription_types', ListPublicSubscriptionTypesHandler::class);

        Route::prefix('api/v2/subscriptions')
            ->middleware('api')
            ->group(base_path('routes/api/subscriptions.php'));
    }
}
