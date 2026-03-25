<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Http\Contracts\Controller;
use App\Http\OpenApi\Schemas as OpenApiSchemas;
use App\Http\Requests\Identity\GoogleLoginRequest;
use App\Http\Requests\Identity\LoginRequest;
use App\Http\Requests\Identity\RequestPasswordResetRequest;
use App\Http\Requests\Identity\RequestVerificationRequest;
use App\Http\Requests\Identity\ResetPasswordRequest;
use App\Http\Requests\Identity\SignUpRequest;
use App\Http\Requests\Identity\UpdateMyProfileRequest;
use App\Http\Requests\Identity\ChangeMyPasswordRequest;
use App\Http\Requests\Identity\ConfirmEmailChangeRequest;
use App\Http\Requests\Identity\RequestEmailChangeRequest;
use App\Http\Requests\Identity\VerifyEmailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\GoogleLogin\GoogleLoginCommand;
use Src\Identity\Application\Commands\Login\LoginCommand;
use Src\Identity\Application\Commands\Logout\LogoutCommand;
use Src\Identity\Application\Commands\RequestPasswordReset\RequestPasswordResetCommand;
use Src\Identity\Application\Commands\RequestVerification\RequestVerificationCommand;
use Src\Identity\Application\Commands\ResetPassword\ResetPasswordCommand;
use Src\Identity\Application\Commands\SignUp\SignUpCommand;
use Src\Identity\Application\Commands\UpdateMyProfile\UpdateMyProfileCommand;
use Src\Identity\Application\Commands\ChangeMyPassword\ChangeMyPasswordCommand;
use Src\Identity\Application\Commands\CancelPendingEmailChange\CancelPendingEmailChangeCommand;
use Src\Identity\Application\Commands\ConfirmEmailChange\ConfirmEmailChangeCommand;
use Src\Identity\Application\Commands\RequestEmailChange\RequestEmailChangeCommand;
use Src\Identity\Application\Commands\VerifyEmail\VerifyEmailCommand;
use Src\Identity\Application\Queries\GetCurrentUser\GetCurrentUserQuery;
use Src\Identity\Application\Resources\LoginResource;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Tag(name: 'Identity', description: 'User authentication, profile, and account management')]
final class IdentityController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Post(
        path: '/identity/sign-up',
        summary: 'Register a new user',
        description: 'Creates a new user account and returns an authentication token.',
        operationId: 'signUp',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'name', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'securePassword123'),
                    new OA\Property(property: 'country', type: 'string', example: 'US'),
                    new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
                    new OA\Property(property: 'language', type: 'string', example: 'en'),
                    new OA\Property(property: 'user_agent', type: 'string', example: 'Mozilla/5.0'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'User created successfully', content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[OA\Post(
        path: '/identity/signup',
        summary: 'Register a new user (alias)',
        description: 'Alias for POST /identity/sign-up. Creates a new user account and returns an authentication token.',
        operationId: 'signUpAlias',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'name', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'securePassword123'),
                    new OA\Property(property: 'country', type: 'string', example: 'US'),
                    new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
                    new OA\Property(property: 'language', type: 'string', example: 'en'),
                    new OA\Property(property: 'user_agent', type: 'string', example: 'Mozilla/5.0'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'User created successfully', content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Post(
        path: '/identity/login',
        summary: 'Authenticate a user',
        description: 'Logs in a user with email and password, returning an access token.',
        operationId: 'login',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'user_agent', type: 'string', example: 'Mozilla/5.0'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful', content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Post(
        path: '/identity/login/google',
        summary: 'Authenticate with Google',
        description: 'Logs in or registers a user using a Google ID token.',
        operationId: 'googleLogin',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id_token'],
                properties: [
                    new OA\Property(property: 'id_token', type: 'string', description: 'Google ID token'),
                    new OA\Property(property: 'user_agent', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Google login successful', content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')),
            new OA\Response(response: 401, description: 'Invalid Google token'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Post(
        path: '/identity/email/request-verification',
        summary: 'Request email verification',
        description: 'Sends a verification email to the user.',
        operationId: 'requestVerification',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Verification email sent', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function requestVerification(RequestVerificationRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new RequestVerificationCommand(
            email: $request->input('email'),
        ));
        return ApiResponseFactory::ok(['message' => 'verification_requested']);
    }

    #[OA\Post(
        path: '/identity/email/verify',
        summary: 'Verify email address',
        description: 'Verifies a user\'s email address using a token.',
        operationId: 'verifyEmail',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', description: 'Verification token'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Email verified successfully', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 422, description: 'Invalid or expired token'),
        ]
    )]
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new VerifyEmailCommand(
            token: $request->input('token'),
        ));
        return ApiResponseFactory::ok($user);
    }

    #[OA\Post(
        path: '/identity/password/request-reset',
        summary: 'Request password reset',
        description: 'Sends a password reset email to the user.',
        operationId: 'requestPasswordReset',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password reset email sent', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function requestPasswordReset(RequestPasswordResetRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new RequestPasswordResetCommand(
            email: $request->input('email'),
        ));
        return ApiResponseFactory::ok(['message' => 'password_reset_requested']);
    }

    #[OA\Post(
        path: '/identity/password/reset',
        summary: 'Reset password',
        description: 'Resets the user\'s password using a token.',
        operationId: 'resetPassword',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'password'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password reset successful', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 422, description: 'Invalid or expired token'),
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new ResetPasswordCommand(
            token: $request->input('token'),
            newHashedPassword: Hash::make($request->input('password')),
        ));
        return ApiResponseFactory::ok($user);
    }

    #[OA\Get(
        path: '/identity/me',
        summary: 'Get current user',
        description: 'Returns the authenticated user\'s profile information.',
        operationId: 'getCurrentUser',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'User profile', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $user = $this->queryBus->dispatch(new GetCurrentUserQuery(userId: $userId));
        return ApiResponseFactory::ok($user);
    }

    #[OA\Patch(
        path: '/identity/me',
        summary: 'Update current user profile',
        description: 'Updates the authenticated user\'s profile information.',
        operationId: 'updateMyProfile',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true),
                    new OA\Property(property: 'country', type: 'string', nullable: true),
                    new OA\Property(property: 'default_locale', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Patch(
        path: '/identity/me/password',
        summary: 'Change current user password',
        description: 'Changes the authenticated user\'s password.',
        operationId: 'changeMyPassword',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'new_password'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password'),
                    new OA\Property(property: 'new_password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Password changed successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid current password'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Post(
        path: '/identity/me/email',
        summary: 'Request email change',
        description: 'Initiates an email change process for the authenticated user.',
        operationId: 'requestEmailChange',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['new_email', 'password'],
                properties: [
                    new OA\Property(property: 'new_email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Email change requested', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid password'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function requestEmailChange(RequestEmailChangeRequest $request): JsonResponse
    {
        $userId = (int) Auth::id();
        $this->commandBus->dispatch(new RequestEmailChangeCommand(
            userId: $userId,
            newEmail: $request->input('new_email'),
            password: $request->input('password'),
        ));
        return ApiResponseFactory::ok(['message' => 'email_change_requested']);
    }

    #[OA\Post(
        path: '/identity/email/confirm-change',
        summary: 'Confirm email change',
        description: 'Confirms a pending email change using a token.',
        operationId: 'confirmEmailChange',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Email changed successfully', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Invalid or expired token'),
        ]
    )]
    public function confirmEmailChange(ConfirmEmailChangeRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new ConfirmEmailChangeCommand(
            token: $request->input('token'),
        ));
        return ApiResponseFactory::ok($user);
    }

    #[OA\Delete(
        path: '/identity/me/email/pending',
        summary: 'Cancel pending email change',
        description: 'Cancels a pending email change for the authenticated user.',
        operationId: 'cancelPendingEmailChange',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Email change cancelled'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function cancelPendingEmailChange(Request $request): Response
    {
        $userId = (int) Auth::id();
        $this->commandBus->dispatch(new CancelPendingEmailChangeCommand(
            userId: $userId,
        ));
        return ApiResponseFactory::noContent();
    }

    #[OA\Post(
        path: '/identity/logout',
        summary: 'Logout current user',
        description: 'Logs out the authenticated user and invalidates the session.',
        operationId: 'logout',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Logged out successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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
