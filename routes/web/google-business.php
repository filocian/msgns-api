<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\GoogleBusiness\Application\Controllers\GoogleBusinessCallbackController;
use Src\GoogleBusiness\Application\Controllers\GoogleBusinessConnectController;

// OAuth initiation — authenticated users only.
Route::get('/google-business/connect', GoogleBusinessConnectController::class)
    ->middleware('auth:stateful-api');

// OAuth callback — web middleware only. Auth is checked manually inside the controller
// because Google's browser redirect must NEVER receive a 401 JSON response.
Route::get('/google-business/callback', GoogleBusinessCallbackController::class);
