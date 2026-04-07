<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\Login\LoginCommand;
use Src\Identity\Application\Resources\LoginResource;
use Src\Identity\Infrastructure\Http\Requests\LoginRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class LoginController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
    public function __invoke(LoginRequest $request): JsonResponse
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
}
