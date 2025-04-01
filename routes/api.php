<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\B4a\DynamoStatsController;
use App\Http\Controllers\Product\AdminProductController;
use App\Http\Controllers\Product\FanceletController;
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
		Route::get('/user/{id}/has-admin-rights', [AuthController::class, 'hasAdminRights']);
		Route::post('/logout', [AuthController::class, 'logout']);
	});
});

/**
 * SPA PRODUCT ROUTES
 */
Route::prefix('products')->group(function () {
	Route::middleware('auth:stateful-api')->group(function () {
		Route::get('/', [ProductController::class, 'list']);
		Route::get('/export', [ProductController::class, 'productListExport']);
		Route::post('/generate', [GenerateProductsController::class, 'generateProducts']);
		Route::get('/mine', [ProductController::class, 'mine']);
		Route::post('/{id}/register/{password}', [ProductController::class, 'register']);
		Route::put('/{id}/configure', [ProductController::class, 'configure']);
		Route::put('/{id}/rename', [ProductController::class, 'rename']);
		Route::post('/{id}/activate', [ProductController::class, 'activate']);
		Route::post('/{id}/deactivate', [ProductController::class, 'deactivate']);
		Route::post('/{id}/business/add', [ProductController::class, 'addBusiness']);

		//Product Grouping
		Route::get('/{id}/group-candidates', [ProductController::class, 'getGroupCandidates']);
		Route::put('/{referenceId}/group/{candidateId}', [ProductController::class, 'setProductGroup']);

		//Whatsapp
		Route::get('/{id}/whatsapp/phones', [ProductController::class, 'getProductWhatsappPhones']);
		Route::get('/{id}/whatsapp/messages', [ProductController::class, 'getProductWhatsappMessages']);
		Route::get('/{id}/whatsapp/copy/{source_id}', [ProductController::class, 'copyProductWhatsappMessages']);
		Route::post('/{id}/whatsapp/add/initial', [ProductController::class, 'addProductWhatsappInitialData']);
		Route::post('/{id}/whatsapp/add/phone', [ProductController::class, 'addProductWhatsappPhone']);
		Route::post('/{id}/whatsapp/remove/phone', [ProductController::class, 'removeProductWhatsappPhone']);
		Route::post('/{id}/whatsapp/add/message', [ProductController::class, 'addProductWhatsappMessage']);
		Route::post('/{id}/whatsapp/remove/message', [ProductController::class, 'removeProductWhatsappMessage']);
		Route::put(
			'/{id}/whatsapp/default/message/{message_id}',
			[ProductController::class, 'setDefaultProductWhatsappMessage']
		);

		//ADMIN EndPoints
		Route::post('/{id}/assign', [AdminProductController::class, 'assignToUser']);
		Route::post('/{id}/reset', [AdminProductController::class, 'resetProduct']);
		Route::put('/{child_id}/remove-product-link', [AdminProductController::class, 'removeProductLink']);
		Route::get('/config-status-list', [ProductController::class, 'getProductConfigStatusList']);
		Route::put('/{id}/set-config-status', [ProductController::class, 'setProductConfigStatus']);
	});

	Route::get('/{id}/{password?}', [ProductController::class, 'findById']);
	Route::get('/{id}/{password}/get-target', [ProductController::class, 'redirect']);
});

Route::prefix('fancelets')->group(function () {
	Route::prefix('get-content')->group(function () {
		Route::get('/lo/{id}/{password}', [FanceletController::class, 'getLoveContent']);
		Route::get('/bi/{id}/{password}', [FanceletController::class, 'getBibleContent']);
	});

	Route::prefix('action')->group(function () {
		Route::post('/lo/message', [FanceletController::class, 'loveAction']);
		Route::post('/bi/message', [FanceletController::class, 'bibleAction']);
	});

	Route::prefix('comments')->group(function () {
		Route::post('/send', [FanceletController::class, 'sendComment']);
		Route::get('/get/{group_id}', [FanceletController::class, 'getGroupComments']);
	});

	Route::prefix('group')->group(function () {
		Route::post('/', [FanceletController::class, 'groupFancelets']);
	});


	Route::post('like/{id}/{password}/{contentType}/{contentId}', [FanceletController::class, 'addContentLike']);
	Route::get('can-like/{id}/{contentType}/{contentId}', [FanceletController::class, 'canLike']);
});

Route::prefix('stats')->group(function () {
	Route::middleware('auth:stateful-api')->group(function () {
		Route::get('/{user_id}/account/overview', [DynamoStatsController::class, 'getIntervalAccountStats']);
		Route::get('/{product_id}/get-interval', [DynamoStatsController::class, 'getIntervalProductStats']);
		Route::get('/{product_id}/get-last-month', [DynamoStatsController::class, 'getLastMonthProductStats']);
		Route::get('/{product_id}/get-current-month', [DynamoStatsController::class, 'getCurrentMonthProductStats']);
		Route::post('/{product_id}/seed', [DynamoStatsController::class, 'seedTestData']);
	});
});

Route::prefix('users')->group(function () {
	Route::middleware('auth:stateful-api')->group(function () {
		Route::get('/', [UsersController::class, 'list']);
		Route::get('/export', [UsersController::class, 'userListExport']);
		Route::get('/list-roles', [UsersController::class, 'listRoles']);
		Route::get('/{id}', [UsersController::class, 'find']);
		Route::put('/{id}/set-roles', [UsersController::class, 'setUserRoles']);
		Route::put('/{id}/set-password', [PasswordResetController::class, 'setUserPassword']);
		Route::put('/{id}/set-email-verified', [VerificationController::class, 'setEmailVerified']);
		Route::put('/{id}/update-profile-data', [UsersController::class, 'updateUserdata']);
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
