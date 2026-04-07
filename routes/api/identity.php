<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Identity\Infrastructure\Http\Controllers\AdminActivateUserController;
use Src\Identity\Infrastructure\Http\Controllers\AdminAssignRoleToUserController;
use Src\Identity\Infrastructure\Http\Controllers\AdminBulkActivationController;
use Src\Identity\Infrastructure\Http\Controllers\AdminBulkAssignRolesController;
use Src\Identity\Infrastructure\Http\Controllers\AdminBulkChangeEmailController;
use Src\Identity\Infrastructure\Http\Controllers\AdminBulkPasswordResetController;
use Src\Identity\Infrastructure\Http\Controllers\AdminBulkVerifyEmailController;
use Src\Identity\Infrastructure\Http\Controllers\AdminCreateRoleController;
use Src\Identity\Infrastructure\Http\Controllers\AdminDeactivateUserController;
use Src\Identity\Infrastructure\Http\Controllers\AdminDeleteRoleController;
use Src\Identity\Infrastructure\Http\Controllers\AdminExportUsersController;
use Src\Identity\Infrastructure\Http\Controllers\AdminGetUserController;
use Src\Identity\Infrastructure\Http\Controllers\AdminListPermissionsController;
use Src\Identity\Infrastructure\Http\Controllers\AdminListRolesController;
use Src\Identity\Infrastructure\Http\Controllers\AdminListUsersController;
use Src\Identity\Infrastructure\Http\Controllers\AdminRemoveRoleFromUserController;
use Src\Identity\Infrastructure\Http\Controllers\AdminSetEmailVerifiedController;
use Src\Identity\Infrastructure\Http\Controllers\AdminSetPasswordController;
use Src\Identity\Infrastructure\Http\Controllers\AdminUpdateRoleController;
use Src\Identity\Infrastructure\Http\Controllers\AdminUpdateUserController;
use Src\Identity\Infrastructure\Http\Controllers\CancelPendingEmailChangeController;
use Src\Identity\Infrastructure\Http\Controllers\ChangeMyPasswordController;
use Src\Identity\Infrastructure\Http\Controllers\ConfirmEmailChangeController;
use Src\Identity\Infrastructure\Http\Controllers\GetCurrentUserController;
use Src\Identity\Infrastructure\Http\Controllers\GoogleLoginController;
use Src\Identity\Infrastructure\Http\Controllers\LoginController;
use Src\Identity\Infrastructure\Http\Controllers\LogoutController;
use Src\Identity\Infrastructure\Http\Controllers\RequestEmailChangeController;
use Src\Identity\Infrastructure\Http\Controllers\RequestPasswordResetController;
use Src\Identity\Infrastructure\Http\Controllers\RequestVerificationController;
use Src\Identity\Infrastructure\Http\Controllers\ResetPasswordController;
use Src\Identity\Infrastructure\Http\Controllers\SignUpController;
use Src\Identity\Infrastructure\Http\Controllers\StartImpersonationController;
use Src\Identity\Infrastructure\Http\Controllers\StopImpersonationController;
use Src\Identity\Infrastructure\Http\Controllers\UpdateMyProfileController;
use Src\Identity\Infrastructure\Http\Controllers\VerifyEmailController;

// Public routes (no auth required)
Route::post('/sign-up', SignUpController::class);
Route::post('/signup', SignUpController::class);
Route::post('/login', LoginController::class)->middleware('throttle:10,1');
Route::post('/login/google', GoogleLoginController::class);
Route::post('/email/request-verification', RequestVerificationController::class);
Route::post('/email/verify', VerifyEmailController::class);
Route::post('/email/confirm-change', ConfirmEmailChangeController::class);
Route::post('/password/request-reset', RequestPasswordResetController::class);
Route::post('/password/reset', ResetPasswordController::class);

// Authenticated routes
Route::middleware('auth:stateful-api')->group(function () {
    Route::get('/me', GetCurrentUserController::class);
    Route::patch('/me', UpdateMyProfileController::class);
    Route::patch('/me/password', ChangeMyPasswordController::class)->middleware('throttle:5,1');
    Route::post('/me/email', RequestEmailChangeController::class)->middleware('throttle:3,60');
    Route::delete('/me/email/pending', CancelPendingEmailChangeController::class);
    Route::post('/logout', LogoutController::class);

    // CRITICAL: /impersonate/stop MUST come before /impersonate/{id}
    Route::post('/impersonate/stop', StopImpersonationController::class);
    Route::post('/impersonate/{id}', StartImpersonationController::class)
         ->middleware('role:developer|backoffice');
});

// Admin routes
Route::middleware(['auth:stateful-api', 'role:developer|backoffice'])->prefix('/admin')->group(function () {
    // IMPORTANT: /users/export MUST come before /users/bulk/* and /users/{id} to avoid route parameter capture
    Route::get('/users/export', AdminExportUsersController::class)
         ->middleware('throttle:10,1');

    // Bulk routes - MUST come before /users/{id}
    Route::prefix('/users/bulk')->group(function () {
        Route::post('/verify-email', AdminBulkVerifyEmailController::class);
        Route::post('/email', AdminBulkChangeEmailController::class);
        Route::post('/activation', AdminBulkActivationController::class);
        Route::post('/roles', AdminBulkAssignRolesController::class);
        Route::post('/password-reset', AdminBulkPasswordResetController::class);
    });

    Route::get('/users', AdminListUsersController::class);
    Route::get('/users/{id}', AdminGetUserController::class);
    Route::patch('/users/{id}', AdminUpdateUserController::class);
    Route::patch('/users/{id}/deactivate', AdminDeactivateUserController::class);
    Route::patch('/users/{id}/activate', AdminActivateUserController::class);
    Route::put('/users/{id}/password', AdminSetPasswordController::class);
    Route::patch('/users/{id}/verify-email', AdminSetEmailVerifiedController::class);
    Route::post('/users/{id}/roles', AdminAssignRoleToUserController::class);
    Route::delete('/users/{id}/roles/{role}', AdminRemoveRoleFromUserController::class);
    Route::get('/roles', AdminListRolesController::class);
    Route::post('/roles', AdminCreateRoleController::class);
    Route::patch('/roles/{id}', AdminUpdateRoleController::class);
    Route::delete('/roles/{id}', AdminDeleteRoleController::class);
    Route::get('/permissions', AdminListPermissionsController::class);
});
