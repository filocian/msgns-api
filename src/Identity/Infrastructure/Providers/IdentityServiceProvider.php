<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Identity\Infrastructure\Http\RoleMiddleware;
use Src\Identity\Application\Commands\AdminActivateUser\AdminActivateUserHandler;
use Src\Identity\Application\Commands\AdminDeactivateUser\AdminDeactivateUserHandler;
use Src\Identity\Application\Commands\AdminSetEmailVerified\AdminSetEmailVerifiedHandler;
use Src\Identity\Application\Commands\AdminSetPassword\AdminSetPasswordHandler;
use Src\Identity\Application\Commands\AdminUpdateUser\AdminUpdateUserHandler;
use Src\Identity\Application\Commands\AssignRole\AssignRoleHandler;
use Src\Identity\Application\Commands\CreateRole\CreateRoleHandler;
use Src\Identity\Application\Commands\DeleteRole\DeleteRoleHandler;
use Src\Identity\Application\Commands\GoogleLogin\GoogleLoginHandler;
use Src\Identity\Application\Commands\Login\LoginHandler;
use Src\Identity\Application\Commands\Logout\LogoutHandler;
use Src\Identity\Application\Commands\ReconcileRbacCatalog\ReconcileRbacCatalogHandler;
use Src\Identity\Application\Commands\RemoveRole\RemoveRoleHandler;
use Src\Identity\Application\Commands\RequestPasswordReset\RequestPasswordResetHandler;
use Src\Identity\Application\Commands\RequestVerification\RequestVerificationHandler;
use Src\Identity\Application\Commands\ResetPassword\ResetPasswordHandler;
use Src\Identity\Application\Commands\ChangeMyPassword\ChangeMyPasswordHandler;
use Src\Identity\Application\Commands\SignUp\SignUpHandler;
use Src\Identity\Application\Commands\StartImpersonation\StartImpersonationHandler;
use Src\Identity\Application\Commands\UpdateMyProfile\UpdateMyProfileHandler;
use Src\Identity\Application\Commands\StopImpersonation\StopImpersonationHandler;
use Src\Identity\Application\Commands\UpdateRole\UpdateRoleHandler;
use Src\Identity\Application\Commands\VerifyEmail\VerifyEmailHandler;
use Src\Identity\Application\Contracts\LocaleMapper;
use Src\Identity\Application\Queries\GetCurrentUser\GetCurrentUserHandler;
use Src\Identity\Application\Queries\GetUser\GetUserHandler;
use Src\Identity\Application\Queries\ListPermissions\ListPermissionsHandler;
use Src\Identity\Application\Queries\ListRoles\ListRolesHandler;
use Src\Identity\Application\Queries\ListUsers\ListUsersHandler;
use Src\Identity\Domain\Events\ImpersonationStarted;
use Src\Identity\Domain\Events\PasswordReset;
use Src\Identity\Domain\Events\PasswordResetRequested;
use Src\Identity\Domain\Events\UserLoggedIn;
use Src\Identity\Domain\Events\UserRegistered;
use Src\Identity\Domain\Events\VerificationRequested;
use Src\Identity\Domain\Ports\GoogleAuthPort;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\ImpersonationPort;
use Src\Identity\Domain\Ports\PasswordHasherPort;
use Src\Identity\Domain\Ports\PasswordResetTokenPort;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Domain\Ports\VerificationTokenPort;
use Src\Identity\Infrastructure\Auth\EncryptedPasswordResetToken;
use Src\Identity\Infrastructure\Auth\EncryptedVerificationToken;
use Src\Identity\Infrastructure\Auth\GoogleOAuthAdapter;
use Src\Identity\Infrastructure\Auth\LaravelPasswordHasherAdapter;
use Src\Identity\Infrastructure\Auth\SessionImpersonationAdapter;
use Src\Identity\Infrastructure\Authorization\SpatieRoleAdapter;
use Src\Identity\Infrastructure\Listeners\LogImpersonation;
use Src\Identity\Infrastructure\Listeners\SendPasswordResetEmail;
use Src\Identity\Infrastructure\Listeners\SendVerificationEmailOnRegistration;
use Src\Identity\Infrastructure\Listeners\SendVerificationEmailOnRequest;
use Src\Identity\Infrastructure\Listeners\TrackPasswordReset;
use Src\Identity\Infrastructure\Listeners\TrackUserLogin;
use Src\Identity\Infrastructure\Listeners\TrackUserRegistration;
use Src\Identity\Infrastructure\Localization\LegacyLocaleMapper;
use Src\Identity\Infrastructure\Persistence\EloquentIdentityUserRepository;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IdentityUserRepository::class, EloquentIdentityUserRepository::class);
        $this->app->bind(ImpersonationPort::class, SessionImpersonationAdapter::class);
        $this->app->bind(RolePort::class, SpatieRoleAdapter::class);
        $this->app->bind(VerificationTokenPort::class, EncryptedVerificationToken::class);
        $this->app->bind(PasswordResetTokenPort::class, EncryptedPasswordResetToken::class);
        $this->app->bind(GoogleAuthPort::class, GoogleOAuthAdapter::class);
        $this->app->bind(PasswordHasherPort::class, LaravelPasswordHasherAdapter::class);
        $this->app->bind(LocaleMapper::class, LegacyLocaleMapper::class);
    }

    public function boot(): void
    {
        // Register command handlers
        $commandBus = $this->app->make(CommandBus::class);
        $commandBus->register('identity.sign_up', SignUpHandler::class);
        $commandBus->register('identity.login', LoginHandler::class);
        $commandBus->register('identity.google_login', GoogleLoginHandler::class);
        $commandBus->register('identity.logout', LogoutHandler::class);
        $commandBus->register('identity.request_verification', RequestVerificationHandler::class);
        $commandBus->register('identity.verify_email', VerifyEmailHandler::class);
        $commandBus->register('identity.request_password_reset', RequestPasswordResetHandler::class);
        $commandBus->register('identity.reset_password', ResetPasswordHandler::class);
        $commandBus->register('identity.start_impersonation', StartImpersonationHandler::class);
        $commandBus->register('identity.stop_impersonation', StopImpersonationHandler::class);
        $commandBus->register('identity.admin_update_user', AdminUpdateUserHandler::class);
        $commandBus->register('identity.admin_deactivate_user', AdminDeactivateUserHandler::class);
        $commandBus->register('identity.admin_activate_user', AdminActivateUserHandler::class);
        $commandBus->register('identity.update_my_profile', UpdateMyProfileHandler::class);
        $commandBus->register('identity.change_my_password', ChangeMyPasswordHandler::class);
        $commandBus->register('identity.admin_set_password', AdminSetPasswordHandler::class);
        $commandBus->register('identity.admin_set_email_verified', AdminSetEmailVerifiedHandler::class);
        $commandBus->register('identity.create_role', CreateRoleHandler::class);
        $commandBus->register('identity.update_role', UpdateRoleHandler::class);
        $commandBus->register('identity.delete_role', DeleteRoleHandler::class);
        $commandBus->register('identity.assign_role', AssignRoleHandler::class);
        $commandBus->register('identity.remove_role', RemoveRoleHandler::class);
        $commandBus->register('identity.reconcile_rbac_catalog', ReconcileRbacCatalogHandler::class);

        // Register query handlers
        $queryBus = $this->app->make(QueryBus::class);
        $queryBus->register('identity.get_current_user', GetCurrentUserHandler::class);
        $queryBus->register('identity.get_user', GetUserHandler::class);
        $queryBus->register('identity.list_users', ListUsersHandler::class);
        $queryBus->register('identity.list_roles', ListRolesHandler::class);
        $queryBus->register('identity.list_permissions', ListPermissionsHandler::class);

        // Register role middleware alias (needed for route-level ->middleware('role:...'))
        $this->app->make(Router::class)->aliasMiddleware('role', RoleMiddleware::class);

        // Load routes
        Route::prefix('api/v2/identity')
            ->middleware('api')
            ->group(base_path('routes/api/identity.php'));

        // Wire event listeners
        Event::listen(UserRegistered::class, SendVerificationEmailOnRegistration::class);
        Event::listen(VerificationRequested::class, SendVerificationEmailOnRequest::class);
        Event::listen(UserRegistered::class, TrackUserRegistration::class);
        Event::listen(UserLoggedIn::class, TrackUserLogin::class);
        Event::listen(ImpersonationStarted::class, LogImpersonation::class);
        Event::listen(PasswordResetRequested::class, SendPasswordResetEmail::class);
        Event::listen(PasswordReset::class, TrackPasswordReset::class);
    }
}
