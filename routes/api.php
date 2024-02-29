<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/** PERSONAL API TOKENS WITH SANCTUM! */

Route::get('/hello', function () {
    return response()->json('hi');
});

Route::prefix('auth')->group(function () {
    Route::post('/hello', function () {
        return response()->json('hi auth');
    });
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/sign-up', [AuthController::class, 'signUp']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });
});
