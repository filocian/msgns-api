<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\Login\LoginCommand;
use Src\Identity\Application\Commands\SignUp\SignUpCommand;
use Src\Identity\Application\Resources\LoginResource;
use Src\Identity\Infrastructure\Http\Requests\SignUpRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class SignUpController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
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
    public function __invoke(SignUpRequest $request): JsonResponse
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
}
