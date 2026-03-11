<?php

declare(strict_types=1);

use App\Http\Controllers\Identity\AdminPermissionController;
use App\Http\Controllers\Identity\AdminRoleController;
use App\Http\Controllers\Identity\AdminUserController;
use App\Http\Controllers\Identity\IdentityController;
use App\Http\Controllers\Identity\ImpersonationController;
use Illuminate\Support\Facades\Route;

// Public routes (no auth required)
Route::post('/sign-up', [IdentityController::class, 'signUp']);
Route::post('/login', [IdentityController::class, 'login'])->middleware('throttle:10,1');
Route::post('/login/google', [IdentityController::class, 'googleLogin']);
Route::post('/email/request-verification', [IdentityController::class, 'requestVerification']);
Route::post('/email/verify', [IdentityController::class, 'verifyEmail']);
Route::post('/password/request-reset', [IdentityController::class, 'requestPasswordReset']);
Route::post('/password/reset', [IdentityController::class, 'resetPassword']);

// Authenticated routes
Route::middleware('auth:stateful-api')->group(function () {
    Route::get('/me', [IdentityController::class, 'me']);
    Route::post('/logout', [IdentityController::class, 'logout']);

    // CRITICAL: /impersonate/stop MUST come before /impersonate/{id}
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop']);
    Route::post('/impersonate/{id}', [ImpersonationController::class, 'start'])
         ->middleware('role:developer|backoffice');
});

// Admin routes
Route::middleware(['auth:stateful-api', 'role:developer|backoffice'])->prefix('/admin')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::patch('/users/{id}', [AdminUserController::class, 'update']);
    Route::patch('/users/{id}/deactivate', [AdminUserController::class, 'deactivate']);
    Route::patch('/users/{id}/activate', [AdminUserController::class, 'activate']);
    Route::post('/users/{id}/roles', [AdminRoleController::class, 'assignToUser']);
    Route::delete('/users/{id}/roles/{role}', [AdminRoleController::class, 'removeFromUser']);
    Route::get('/roles', [AdminRoleController::class, 'index']);
    Route::post('/roles', [AdminRoleController::class, 'store']);
    Route::patch('/roles/{id}', [AdminRoleController::class, 'update']);
    Route::delete('/roles/{id}', [AdminRoleController::class, 'destroy']);
    Route::get('/permissions', [AdminPermissionController::class, 'index']);
});
