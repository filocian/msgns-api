<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/**
 * AUTH ROUTES
 */
Route::prefix('auth')->group(function () {
	Route::get('/hello', [AuthController::class, 'hello']);
	Route::post('/login', [AuthController::class, 'login']);
	Route::post('/sign-up', [AuthController::class, 'signUp']);
	Route::post('/login/social/google', [AuthController::class, 'googleLogin']);

	Route::middleware('auth:sanctum')->group(function () {
		Route::get('/user', [AuthController::class, 'currentUser']);
		Route::post('/logout', [AuthController::class, 'logout']);
	});
});
