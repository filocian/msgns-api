<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Ai\Infrastructure\Http\Controllers\ClassicSubscriptionController;
use Src\Ai\Infrastructure\Http\Controllers\SubscriptionTypeCatalogController;
use Src\Ai\Infrastructure\Http\Controllers\UserAiSystemPromptController;

// Public — no auth required
Route::get('/subscription-types', [SubscriptionTypeCatalogController::class, 'index']);

Route::middleware(['auth:stateful-api', 'ai.rate-limit'])->group(function (): void {
    // AI routes — extended by BE-3, BE-8, BE-10, BE-12, BE-13
});

Route::middleware(['auth:stateful-api'])->group(function (): void {
    Route::get('/system-prompts', [UserAiSystemPromptController::class, 'index']);
    Route::put('/system-prompts/{product_type}', [UserAiSystemPromptController::class, 'upsert']);
    Route::delete('/system-prompts/{product_type}', [UserAiSystemPromptController::class, 'destroy']);

    // BE-5: Classic AI subscriptions
    Route::post('/subscriptions/classic', [ClassicSubscriptionController::class, 'subscribe']);
    Route::delete('/subscriptions/classic', [ClassicSubscriptionController::class, 'cancel']);
    Route::get('/subscriptions/classic', [ClassicSubscriptionController::class, 'show']);
});
