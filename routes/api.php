<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Product\AdminProductController;
use App\Http\Controllers\Product\GenerateProductsController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\User\UsersController;
use Illuminate\Support\Facades\Route;

/**
 * SPA AUTH ROUTES
 */
Route::prefix('auth')->group(function () {
	Route::get('/hello', [AuthController::class, 'hello']);
	Route::post('/login', [AuthController::class, 'login']);
	Route::post('/sign-up', [AuthController::class, 'signUp']);
	Route::post('/login/social/google', [AuthController::class, 'googleLogin']);
	Route::post('/email/verify', [VerificationController::class, 'verify']);
	Route::post('/email/request-verification', [VerificationController::class, 'sendVerificationEmail']);
	Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
	Route::post('/password/request-reset', [PasswordResetController::class, 'sendPassResetEmail']);

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

		Route::get('/', [ProductController::class, 'list']);
		Route::post('/generate', [GenerateProductsController::class, 'generateProducts']);
		Route::get('/mine', [ProductController::class, 'mine']);
//		Route::post('/{id}/assign/{password}', [ProductController::class, 'assignToCurrentUser']);
		Route::post('/{id}/assign/{userId}', [AdminProductController::class, 'assignToUser']);
		Route::post('/{id}/register/{password}', [ProductController::class, 'register']);
		Route::put('/{id}/configure', [ProductController::class, 'configure']);
		Route::put('/{id}/rename', [ProductController::class, 'rename']);
		Route::post('/{id}/activate', [ProductController::class, 'activate']);
		Route::post('/{id}/deactivate', [ProductController::class, 'deactivate']);
		Route::post('/{id}/business/add', [ProductController::class, 'addBusiness']);

		Route::get('/{id}/parent-candidates', [ProductController::class, 'getParentCandidates']);
		Route::get('/{id}/child-candidates', [ProductController::class, 'getChildCandidates']);
		Route::put('/{id}/set-child/{child_id}', [ProductController::class, 'setChildProduct']);
		Route::put('/{id}/set-parent/{parent_id}', [ProductController::class, 'setParentProduct']);
	});

	Route::get('/{id}', [ProductController::class, 'findById']);
//	Route::get('/searches-place', [ProductController::class, 'searchPlace']);
});

Route::prefix('users')->group(function () {
	Route::middleware('auth:stateful-api')->group(function () {
		Route::get('/', [UsersController::class, 'list']);
	});
});
/**
 * Places External API
 */
Route::prefix('places')->group(function () {
	Route::middleware('auth:stateful-api')->group(function () {
		Route::get('/search', [ProductController::class, 'searchPlace']);
	});
});

