<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\GoogleBusiness\Application\Controllers\GoogleBusinessConnectionController;

Route::middleware(['auth:stateful-api'])->group(function (): void {
    Route::get('/connection', [GoogleBusinessConnectionController::class, 'show']);
    Route::delete('/connection', [GoogleBusinessConnectionController::class, 'destroy']);
});
