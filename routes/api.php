<?php

use App\Http\Contracts\HttpJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;


Route::get('/hello', function () {
    return HttpJson::OK('hi');
});

Route::prefix('auth')->group(function () {
    Route::post('/hello', function () {
        return HttpJson::OK('hi auth');
    });
    Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class,'show'])->name('auth.sanctum.csrf-cookie');

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/sign-up', [AuthController::class, 'signUp']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class,'currentUser']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
