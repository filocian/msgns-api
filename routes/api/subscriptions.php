<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Subscriptions\Infrastructure\Http\Controllers\AdminCreateSubscriptionTypeController;
use Src\Subscriptions\Infrastructure\Http\Controllers\AdminDeleteSubscriptionTypeController;
use Src\Subscriptions\Infrastructure\Http\Controllers\AdminGetSubscriptionTypeController;
use Src\Subscriptions\Infrastructure\Http\Controllers\AdminListSubscriptionTypesController;
use Src\Subscriptions\Infrastructure\Http\Controllers\AdminToggleSubscriptionTypeActiveController;
use Src\Subscriptions\Infrastructure\Http\Controllers\AdminUpdateSubscriptionTypeController;
use Src\Subscriptions\Infrastructure\Http\Controllers\ListPublicSubscriptionTypesController;

// Public — no auth
Route::get('/subscription-types', ListPublicSubscriptionTypesController::class);

// Admin — requires auth + permission
Route::middleware(['auth:stateful-api', 'permission:manage_subscription_types,stateful-api'])
    ->prefix('/admin/subscription-types')
    ->group(function (): void {
        Route::get('/', AdminListSubscriptionTypesController::class);
        Route::post('/', AdminCreateSubscriptionTypeController::class);
        Route::get('/{id}', AdminGetSubscriptionTypeController::class)->whereNumber('id');
        Route::put('/{id}', AdminUpdateSubscriptionTypeController::class)->whereNumber('id');
        Route::patch('/{id}/toggle-active', AdminToggleSubscriptionTypeActiveController::class)->whereNumber('id');
        Route::delete('/{id}', AdminDeleteSubscriptionTypeController::class)->whereNumber('id');
    });
