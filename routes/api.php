<?php

use App\Http\Contracts\HttpJson;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ProductController;
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
 * NFC ROUTES
 */

Route::prefix('nfc')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/mine', [ProductController::class, 'mine']);
        Route::post('/', [ProductController::class, 'store']);
        Route::post('/{id}/activate', [ProductController::class, 'activate']);
        Route::put('/{id}', [ProductController::class, 'update']);
        //Route::get('/find', [ProductController::class, 'find']);
    });

    Route::post('/hello', [ProductController::class, 'hello']);
    Route::get('/{id}', [ProductController::class, 'findById']);
});

Route::prefix('product')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{id}', [ProductController::class, 'findById']);
        Route::get('/{id}/activate/{password}', [ProductController::class, 'assignToCurrentUser']);

    });

    Route::post('/hello', [ProductController::class, 'hello']);
    Route::get('/{id}', [ProductController::class, 'findById']);
});
