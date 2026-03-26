<?php

declare(strict_types=1);

use App\Http\Controllers\Products\GenerateProductsController;
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
    Route::post('/{id}/usage', [ProductUsageController::class, 'store']);

    // Batch product generation — issue #10
    Route::post('/generate', [GenerateProductsController::class, 'generate']);
});
