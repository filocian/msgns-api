<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Ai\Infrastructure\Http\Controllers\AiResponseController;
use Src\Ai\Infrastructure\Http\Controllers\ClassicSubscriptionController;
use Src\Ai\Infrastructure\Http\Controllers\GetPrepaidBalancesController;
use Src\Ai\Infrastructure\Http\Controllers\GetPrepaidPackagesController;
use Src\Ai\Infrastructure\Http\Controllers\PurchasePrepaidPackageController;
use Src\Ai\Infrastructure\Http\Controllers\SubscriptionTypeCatalogController;
use Src\Ai\Infrastructure\Http\Controllers\UserAiSystemPromptController;
use Src\GoogleBusiness\Infrastructure\Http\Controllers\GoogleReviewsController;
use Src\Instagram\Infrastructure\Http\Controllers\GenerateInstagramCaptionController;

// Public — no auth required
Route::get('/subscription-types', [SubscriptionTypeCatalogController::class, 'index']);

// BE-6: Prepaid packages catalog (public)
Route::get('/prepaid-packages', GetPrepaidPackagesController::class);

Route::middleware(['auth:stateful-api', 'ai.rate-limit'])->group(function (): void {
    // BE-12: Google Reviews AI generation routes (add here with ai.enforce-usage:google_reviews)
    Route::middleware(['ai.enforce-usage:google_reviews'])->group(function (): void {
        Route::get('/google/reviews', [GoogleReviewsController::class, 'index']);
        Route::post('/google/reviews/{reviewId}/generate', [GoogleReviewsController::class, 'generate']);
    });

    // BE-13: Instagram AI generation routes (add here with ai.enforce-usage:instagram)
    Route::middleware(['ai.enforce-usage:instagram'])->group(function (): void {
        Route::post('/instagram/generate', GenerateInstagramCaptionController::class);
    });
});

Route::middleware(['auth:stateful-api'])->group(function (): void {
    Route::get('/system-prompts', [UserAiSystemPromptController::class, 'index']);
    Route::put('/system-prompts/{product_type}', [UserAiSystemPromptController::class, 'upsert']);
    Route::delete('/system-prompts/{product_type}', [UserAiSystemPromptController::class, 'destroy']);

    // BE-5: Classic AI subscriptions
    Route::post('/subscriptions/classic', [ClassicSubscriptionController::class, 'subscribe']);
    Route::delete('/subscriptions/classic', [ClassicSubscriptionController::class, 'cancel']);
    Route::get('/subscriptions/classic', [ClassicSubscriptionController::class, 'show']);

    // BE-6: Prepaid package purchase and balance query
    Route::post('/packages/purchase', PurchasePrepaidPackageController::class);
    Route::get('/packages/balances', GetPrepaidBalancesController::class);

    // BE-10: AI Response Lifecycle (Human-in-the-Loop)
    Route::prefix('responses')->group(function (): void {
        Route::get('/', [AiResponseController::class, 'index']);
        Route::patch('/{id}/approve', [AiResponseController::class, 'approve'])->whereUuid('id');
        Route::patch('/{id}/edit', [AiResponseController::class, 'edit'])->whereUuid('id');
        Route::patch('/{id}/reject', [AiResponseController::class, 'reject'])->whereUuid('id');
        Route::post('/{id}/apply', [AiResponseController::class, 'apply'])->whereUuid('id');
    });
});
