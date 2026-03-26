<?php

declare(strict_types=1);

use App\Http\Controllers\Products\GenerateProductsController;
use App\Http\Controllers\Products\ProductActionController;
use App\Http\Controllers\Products\ProductTypeController;
use App\Http\Controllers\Products\ProductUsageController;
use Illuminate\Support\Facades\Route;

// Products module v2 routes.
// Loaded by ProductsServiceProvider with prefix `api/v2/products` and `api` middleware.

Route::middleware('auth:stateful-api')->group(function (): void {
    Route::get('/product-types', [ProductTypeController::class, 'index']);
    Route::get('/product-types/{id}', [ProductTypeController::class, 'show']);
    Route::post('/product-types', [ProductTypeController::class, 'store']);
    Route::patch('/product-types/{id}', [ProductTypeController::class, 'update']);

    // Product usage — issue #13
    Route::post('/{id}/usage', [ProductUsageController::class, 'store'])->whereNumber('id');

    // Basic product actions — issue #11
    Route::patch('/{id}/assign', [ProductActionController::class, 'assignToUser'])->whereNumber('id');
    Route::patch('/{id}/target-url', [ProductActionController::class, 'setTargetUrl'])->whereNumber('id');
    Route::post('/{id}/activate', [ProductActionController::class, 'activate'])->whereNumber('id');
    Route::post('/{id}/deactivate', [ProductActionController::class, 'deactivate'])->whereNumber('id');
    Route::patch('/{id}/config-status', [ProductActionController::class, 'changeConfigStatus'])->whereNumber('id');
    Route::patch('/{id}/name', [ProductActionController::class, 'rename'])->whereNumber('id');
    Route::delete('/{id}', [ProductActionController::class, 'softDelete'])->whereNumber('id');
    Route::post('/{id}/restore', [ProductActionController::class, 'restore'])->whereNumber('id');
    Route::delete('/{id}/link', [ProductActionController::class, 'removeLink'])->whereNumber('id');

    // Batch product generation — issue #10
    Route::post('/generate', [GenerateProductsController::class, 'generate']);
});
