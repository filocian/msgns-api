<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Products\Infrastructure\Http\Controllers\ActivateProductController;
use Src\Products\Infrastructure\Http\Controllers\AddBusinessInfoController;
use Src\Products\Infrastructure\Http\Controllers\AssignToUserController;
use Src\Products\Infrastructure\Http\Controllers\ChangeConfigStatusController;
use Src\Products\Infrastructure\Http\Controllers\CloneFromProductController;
use Src\Products\Infrastructure\Http\Controllers\CompleteConfigurationController;
use Src\Products\Infrastructure\Http\Controllers\ConfigureUrlProductController;
use Src\Products\Infrastructure\Http\Controllers\CreateProductTypeController;
use Src\Products\Infrastructure\Http\Controllers\DeactivateProductController;
use Src\Products\Infrastructure\Http\Controllers\DownloadGenerationExcelController;
use Src\Products\Infrastructure\Http\Controllers\GetProductTypeController;
use Src\Products\Infrastructure\Http\Controllers\GroupProductsController;
use Src\Products\Infrastructure\Http\Controllers\ListGenerationHistoryController;
use Src\Products\Infrastructure\Http\Controllers\ListProductTypesController;
use Src\Products\Infrastructure\Http\Controllers\RegisterProductController;
use Src\Products\Infrastructure\Http\Controllers\RemoveProductLinkController;
use Src\Products\Infrastructure\Http\Controllers\ReportUsageController;
use Src\Products\Infrastructure\Http\Controllers\ResetProductController;
use Src\Products\Infrastructure\Http\Controllers\RestoreProductController;
use Src\Products\Infrastructure\Http\Controllers\SetTargetUrlController;
use Src\Products\Infrastructure\Http\Controllers\SoftDeleteProductController;
use Src\Products\Infrastructure\Http\Controllers\UpdateProductDetailsController;
use Src\Products\Infrastructure\Http\Controllers\UpdateProductTypeController;

// Products module v2 routes.
// Loaded by ProductsServiceProvider with prefix `api/v2/products` and `api` middleware.

Route::get('/{id}/{password}/redirection-target', [
    \Src\Products\Infrastructure\Http\Controllers\ProductRedirectionController::class,
    'apiRedirect',
])->whereNumber('id');

Route::middleware('auth:stateful-api')->group(function (): void {
    // User product listing
    Route::get('/', [\Src\Products\Infrastructure\Http\Controllers\UserProductController::class, 'index']);

    Route::middleware('role:developer|backoffice')->prefix('/admin')->group(function (): void {
        Route::get('/', [\Src\Products\Infrastructure\Http\Controllers\AdminProductListController::class, 'index']);
    });

    Route::get('/product-types', ListProductTypesController::class);
    Route::get('/product-types/{id}', GetProductTypeController::class);
    Route::post('/product-types', CreateProductTypeController::class);
    Route::patch('/product-types/{id}', UpdateProductTypeController::class);

    // Product usage — issue #13
    Route::post('/{id}/usage', ReportUsageController::class)->whereNumber('id');

    // Basic product actions — issue #11
    Route::patch('/{id}/assign', AssignToUserController::class)->whereNumber('id');
    Route::patch('/{id}/target-url', SetTargetUrlController::class)->whereNumber('id');
    Route::post('/{id}/activate', ActivateProductController::class)->whereNumber('id');
    Route::post('/{id}/deactivate', DeactivateProductController::class)->whereNumber('id');
    Route::patch('/{id}/config-status', ChangeConfigStatusController::class)->whereNumber('id');
    Route::patch('/{id}/details', UpdateProductDetailsController::class)->whereNumber('id');
    Route::delete('/{id}', SoftDeleteProductController::class)->whereNumber('id');
    Route::post('/{id}/restore', RestoreProductController::class)->whereNumber('id');
    Route::delete('/{id}/link', RemoveProductLinkController::class)->whereNumber('id');

    // Composed actions — issue #12
    Route::post('/{id}/register', RegisterProductController::class)->whereNumber('id');
    Route::put('/{id}/configure', ConfigureUrlProductController::class)->whereNumber('id');
    Route::post('/{id}/complete-configuration', CompleteConfigurationController::class)->whereNumber('id');
    Route::post('/{referenceId}/group/{candidateId}', GroupProductsController::class)
        ->whereNumber('referenceId')
        ->whereNumber('candidateId');
    Route::post('/{id}/clone-from/{sourceId}', CloneFromProductController::class)
        ->whereNumber('id')
        ->whereNumber('sourceId');
    Route::post('/{id}/business', AddBusinessInfoController::class)->whereNumber('id');

    // Reset action — issue #15
    Route::post('/{id}/reset', ResetProductController::class)->whereNumber('id');

    // Batch product generation — issue #10
    Route::post('/generate', \Src\Products\Infrastructure\Http\Controllers\GenerateProductsController::class);

    // Generation history — issue #46
    Route::get('/generations', ListGenerationHistoryController::class);
    Route::get('/generations/{id}/download', DownloadGenerationExcelController::class)->whereNumber('id');

    // WhatsApp configuration — issue #61
    Route::post('/{id}/whatsapp/configure', \Src\Products\Infrastructure\Http\Controllers\ConfigureWhatsappProductController::class)->whereNumber('id');
    Route::post('/{id}/whatsapp/phones', [\Src\Products\Infrastructure\Http\Controllers\WhatsappPhoneController::class, 'store'])->whereNumber('id');
    Route::delete('/{id}/whatsapp/phones/{phoneId}', [\Src\Products\Infrastructure\Http\Controllers\WhatsappPhoneController::class, 'destroy'])
        ->whereNumber('id')
        ->whereNumber('phoneId');
    Route::post('/{id}/whatsapp/messages', [\Src\Products\Infrastructure\Http\Controllers\WhatsappMessageController::class, 'store'])->whereNumber('id');
    Route::delete('/{id}/whatsapp/messages/{messageId}', [\Src\Products\Infrastructure\Http\Controllers\WhatsappMessageController::class, 'destroy'])
        ->whereNumber('id')
        ->whereNumber('messageId');
    Route::patch('/{id}/whatsapp/messages/{messageId}/default', [\Src\Products\Infrastructure\Http\Controllers\WhatsappMessageController::class, 'setDefault'])
        ->whereNumber('id')
        ->whereNumber('messageId');
    Route::get('/whatsapp/locales', \Src\Products\Infrastructure\Http\Controllers\ListWhatsappLocalesController::class);
});
