<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Billing\Infrastructure\Http\Controllers\CreateSetupIntentController;
use Src\Billing\Infrastructure\Http\Controllers\DeletePaymentMethodController;
use Src\Billing\Infrastructure\Http\Controllers\ListPaymentMethodsController;
use Src\Billing\Infrastructure\Http\Controllers\ListStripeProductPricesController;
use Src\Billing\Infrastructure\Http\Controllers\ListStripeProductsController;
use Src\Billing\Infrastructure\Http\Controllers\SetDefaultPaymentMethodController;

Route::middleware('auth:stateful-api')->prefix('me')->group(function (): void {
    Route::get('/payment-methods', ListPaymentMethodsController::class);
    Route::post('/setup-intent', CreateSetupIntentController::class);
    Route::put('/payment-methods/{paymentMethodId}/default', SetDefaultPaymentMethodController::class);
    Route::delete('/payment-methods/{paymentMethodId}', DeletePaymentMethodController::class);
});

Route::middleware(['auth:stateful-api', 'permission:manage_subscription_types,stateful-api'])
    ->prefix('stripe')
    ->group(function (): void {
        Route::get('/products', ListStripeProductsController::class)
            ->name('billing.stripe.products.list');
        Route::get('/products/{productId}/prices', ListStripeProductPricesController::class)
            ->where('productId', '^prod_[A-Za-z0-9]+$')
            ->name('billing.stripe.product-prices.list');
    });
