<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Http\Contracts\Controller;
use App\Http\Requests\Identity\GoogleLoginRequest;
use App\Http\Requests\Identity\LoginRequest;
use App\Http\Requests\Identity\RequestPasswordResetRequest;
use App\Http\Requests\Identity\RequestVerificationRequest;
use App\Http\Requests\Identity\ResetPasswordRequest;
use App\Http\Requests\Identity\SignUpRequest;
use App\Http\Requests\Identity\UpdateMyProfileRequest;
use App\Http\Requests\Identity\ChangeMyPasswordRequest;
use App\Http\Requests\Identity\VerifyEmailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Src\Identity\Application\Commands\GoogleLogin\GoogleLoginCommand;
use Src\Identity\Application\Commands\Login\LoginCommand;
use Src\Identity\Application\Commands\Logout\LogoutCommand;
use Src\Identity\Application\Commands\RequestPasswordReset\RequestPasswordResetCommand;
use Src\Identity\Application\Commands\RequestVerification\RequestVerificationCommand;
use Src\Identity\Application\Commands\ResetPassword\ResetPasswordCommand;
use Src\Identity\Application\Commands\SignUp\SignUpCommand;
use Src\Identity\Application\Commands\UpdateMyProfile\UpdateMyProfileCommand;
use Src\Identity\Application\Commands\ChangeMyPassword\ChangeMyPasswordCommand;
use Src\Identity\Application\Commands\VerifyEmail\VerifyEmailCommand;
use Src\Identity\Application\Queries\GetCurrentUser\GetCurrentUserQuery;
use Src\Identity\Application\Resources\LoginResource;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class IdentityController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function signUp(SignUpRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new SignUpCommand(
            email: $request->input('email'),
            name: $request->input('name'),
            hashedPassword: Hash::make($request->input('password')),
            country: $request->input('country'),
            phone: $request->input('phone'),
            language: $request->input('language'),
            userAgent: $request->input('user_agent'),
        ));

        $user = $this->commandBus->dispatch(new LoginCommand(
            email: $request->input('email'),
            password: $request->input('password'),
            userAgent: $request->input('user_agent'),
        ));

        Auth::guard('stateful-api')->loginUsingId($user->id);
        session()->regenerate();

        return ApiResponseFactory::ok(new LoginResource($user));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new LoginCommand(
            email: $request->input('email'),
            password: $request->input('password'),
            userAgent: $request->input('user_agent'),
        ));
        Auth::guard('stateful-api')->loginUsingId($user->id);
        session()->regenerate();
        return ApiResponseFactory::ok(new LoginResource($user));
    }

    public function googleLogin(GoogleLoginRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new GoogleLoginCommand(
            idToken: $request->input('id_token'),
            userAgent: $request->input('user_agent'),
        ));
        Auth::guard('stateful-api')->loginUsingId($user->id);
        session()->regenerate();
        return ApiResponseFactory::ok(new LoginResource($user));
    }

    public function requestVerification(RequestVerificationRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new RequestVerificationCommand(
            email: $request->input('email'),
        ));
        return ApiResponseFactory::ok(['message' => 'verification_requested']);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new VerifyEmailCommand(
            token: $request->input('token'),
        ));
        return ApiResponseFactory::ok($user);
    }

    public function requestPasswordReset(RequestPasswordResetRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new RequestPasswordResetCommand(
            email: $request->input('email'),
        ));
        return ApiResponseFactory::ok(['message' => 'password_reset_requested']);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new ResetPasswordCommand(
            token: $request->input('token'),
            newHashedPassword: Hash::make($request->input('password')),
        ));
        return ApiResponseFactory::ok($user);
    }

    public function me(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $user = $this->queryBus->dispatch(new GetCurrentUserQuery(userId: $userId));
        return ApiResponseFactory::ok($user);
    }

    public function updateMyProfile(UpdateMyProfileRequest $request): JsonResponse
    {
        $userId = (int) Auth::id();
        $user = $this->commandBus->dispatch(new UpdateMyProfileCommand(
            userId: $userId,
            name: $request->input('name'),
            phone: $request->input('phone'),
            country: $request->input('country'),
            defaultLocale: $request->input('default_locale'),
        ));
        return ApiResponseFactory::ok($user);
    }

    public function changeMyPassword(ChangeMyPasswordRequest $request): Response
    {
        $userId = (int) Auth::id();
        $this->commandBus->dispatch(new ChangeMyPasswordCommand(
            userId: $userId,
            currentPassword: $request->input('current_password'),
            newHashedPassword: Hash::make($request->input('new_password')),
        ));
        return ApiResponseFactory::noContent();
    }

    public function logout(Request $request): Response
    {
        $userId = Auth::id();
        Auth::guard('stateful-api')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        $this->commandBus->dispatch(new LogoutCommand(userId: $userId));
        return ApiResponseFactory::noContent();
    }
}
