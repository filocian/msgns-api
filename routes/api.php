<?php

use App\Http\Contracts\HttpJson;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Product\ProductController;
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

Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'mine']);
        Route::get('/{id}', [ProductController::class, 'findById']);
        Route::post('/{id}/activate', [ProductController::class, 'activate']);
        Route::post('/{id}/deactivate', [ProductController::class, 'deactivate']);
        Route::post('/{id}/assign/{password}', [ProductController::class, 'assignToCurrentUser']);
        Route::put('/{id}/configure', [ProductController::class, 'update']);
    });

//    Route::prefix('account')->group(function () {
//        Route::get('/profile', [AccountController::class, 'getProfile']);
//        Route::put('/profile', [AccountController::class, 'updateProfile']);
//        Route::get('/business', [AccountController::class, 'listBusiness']);
//        Route::post('/business', [AccountController::class, 'listBusiness']);
//        Route::put('/business/{id}', [AccountController::class, 'listBusiness']);
//        Route::delete('/business/{id}', [AccountController::class, 'listBusiness']);
//    });
//
//    Route::prefix('search')->group(function () {
//        Route::get('/products/by-business', [SearchController::class, 'searchByBusiness']);
//        Route::get('/products/by-type', [SearchController::class, 'searchByProductType']);
//        Route::get('/products/by-name', [SearchController::class, 'searchByName']);
//    });
});

//Route::prefix('nfc')->group(function () {
//
//    Route::middleware('auth:sanctum')->group(function () {
//        Route::get('/mine', [ProductController::class, 'mine']);
//        Route::post('/', [ProductController::class, 'store']);
//        Route::post('/{id}/activate', [ProductController::class, 'activate']);
//        Route::put('/{id}', [ProductController::class, 'update']);
//        //Route::get('/find', [ProductController::class, 'find']);
//    });
//
//    Route::post('/hello', [ProductController::class, 'hello']);
//    Route::get('/{id}', [ProductController::class, 'findById']);
//});

//Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
//    Route::prefix('products')->group(function () {
//        Route::get('/{id}', [ProductController::class, 'find']);
//        Route::put('/{id}', [ProductController::class, 'update']);
//        Route::post('/{id}/activate', [ProductController::class, 'activate']);
//        Route::post('/{id}/deactivate', [ProductController::class, 'deactivate']);
//    });
//
//    Route::prefix('account')->group(function () {
//        Route::get('/permissions', [ProductController::class, 'getPermissions']);
//        Route::put('/permissions', [ProductController::class, 'setPermissions']);
//        Route::get('/profile', [ProductController::class, 'getProfile']);
//        Route::put('/profile', [ProductController::class, 'updateProfile']);
//    });
//
//    Route::prefix('search')->group(function () {
//        Route::get('/products/by-business', [ProductController::class, 'searchByBusiness']);
//        Route::get('/products/by-type', [ProductController::class, 'searchByProductType']);
//        Route::put('/products/by-user', [ProductController::class, 'searchByUser']);
//        Route::put('/products/by-name', [ProductController::class, 'searchByUser']);
//    });
//});
