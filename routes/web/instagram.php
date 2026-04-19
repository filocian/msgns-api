<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Instagram\Infrastructure\Http\Controllers\InstagramCallbackController;
use Src\Instagram\Infrastructure\Http\Controllers\InstagramConnectController;

// OAuth initiation — authenticated users only.
Route::get('/instagram/connect', InstagramConnectController::class)
    ->middleware('auth:stateful-api');

// OAuth callback — web middleware only. Auth is checked manually inside the controller
// because Meta's browser redirect must NEVER receive a 401 JSON response.
Route::get('/instagram/callback', InstagramCallbackController::class);
