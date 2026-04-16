<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Billing\Application\Commands\CreateSetupIntent\CreateSetupIntentHandler;
use Src\Billing\Application\Commands\DeletePaymentMethod\DeletePaymentMethodHandler;
use Src\Billing\Application\Commands\ExpireSubscriptionFromStripe\ExpireSubscriptionFromStripeHandler;
use Src\Billing\Application\Commands\HandlePrepaidPaymentFailed\HandlePrepaidPaymentFailedHandler;
use Src\Billing\Application\Commands\HandlePrepaidPaymentSucceeded\HandlePrepaidPaymentSucceededHandler;
use Src\Billing\Application\Commands\SetDefaultPaymentMethod\SetDefaultPaymentMethodHandler;
use Src\Billing\Application\Commands\SyncSubscriptionStatusFromStripe\SyncSubscriptionStatusFromStripeHandler;
use Src\Billing\Application\Queries\ListPaymentMethods\ListPaymentMethodsHandler;
use Src\Billing\Domain\Ports\BillingPort;
use Src\Billing\Infrastructure\Services\StripeCustomerService;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;

final class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BillingPort::class, StripeCustomerService::class);
    }

    public function boot(): void
    {
        $commandBus = $this->app->make(CommandBus::class);
        $commandBus->register('billing.create_setup_intent', CreateSetupIntentHandler::class);
        $commandBus->register('billing.set_default_payment_method', SetDefaultPaymentMethodHandler::class);
        $commandBus->register('billing.delete_payment_method', DeletePaymentMethodHandler::class);
        $commandBus->register('billing.sync_subscription_status', SyncSubscriptionStatusFromStripeHandler::class);
        $commandBus->register('billing.expire_subscription', ExpireSubscriptionFromStripeHandler::class);
        $commandBus->register('billing.handle_prepaid_payment_succeeded', HandlePrepaidPaymentSucceededHandler::class);
        $commandBus->register('billing.handle_prepaid_payment_failed', HandlePrepaidPaymentFailedHandler::class);

        $queryBus = $this->app->make(QueryBus::class);
        $queryBus->register('billing.list_payment_methods', ListPaymentMethodsHandler::class);

        Route::prefix('api/v2/billing')
            ->middleware('api')
            ->group(base_path('routes/api/billing.php'));

        Route::prefix('api/v2/billing')
            ->middleware(['api', \Laravel\Cashier\Http\Middleware\VerifyWebhookSignature::class])
            ->group(base_path('routes/api/billing-webhooks.php'));
    }
}
