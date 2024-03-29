<?php

use App\Http\Contracts\HttpJson;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\NFCController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;


Route::get('/hello', function () {
    return HttpJson::OK('hi');
});

/**
 * AUTH ROUTES
 */
Route::prefix('auth')->group(function () {
    Route::get('/hello', [AuthController::class, 'hello']);
    Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/sign-up', [AuthController::class, 'signUp']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'currentUser']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});


/**
 * PRODUCT ROUTES
 */
Route::get('nfc/{id}', [NFCController::class, 'findById']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('nfc', [NFCController::class, 'find']);
    Route::post('nfc', [NFCController::class, 'store']);
    Route::post('nfc/{id}/activate', [NFCController::class, 'activate']);
    Route::put('nfc/{id}', [NFCController::class, 'update']);
});

