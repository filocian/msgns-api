<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Product\GenerateProductsController;
use App\Http\Controllers\Product\ProductController;
use Illuminate\Support\Facades\Route;

/**
 * SPA AUTH ROUTES
 */
Route::prefix('auth')->group(function () {
	Route::get('/hello', [AuthController::class, 'hello']);
	Route::post('/login', [AuthController::class, 'login']);
	Route::post('/sign-up', [AuthController::class, 'signUp']);
	Route::post('/login/social/google', [AuthController::class, 'googleLogin']);

	Route::middleware('auth:stateful-api')->group(function () {
		Route::get('/user', [AuthController::class, 'currentUser']);
		Route::post('/logout', [AuthController::class, 'logout']);
	});
});

/**
 * SPA PRODUCT ROUTES
 */
Route::prefix('products')->group(function () {
	Route::middleware('auth:stateful-api')->group(function () {
		Route::post('/generate', [GenerateProductsController::class, 'generateProducts']);
		Route::get('/mine', [ProductController::class, 'mine']);
		Route::post('/{id}/assign/{password}', [ProductController::class, 'assignToCurrentUser']);
		Route::post('/{id}/register/{password}', [ProductController::class, 'register']);
		Route::put('/{id}/configure', [ProductController::class, 'configure']);
		Route::put('/{id}/rename', [ProductController::class, 'rename']);
		Route::post('/{id}/activate', [ProductController::class, 'activate']);
		Route::post('/{id}/deactivate', [ProductController::class, 'deactivate']);
	});

	Route::get('/{id}', [ProductController::class, 'findById']);
});
