<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\GoogleLogin\GoogleLoginCommand;
use Src\Identity\Application\Resources\LoginResource;
use Src\Identity\Infrastructure\Http\Requests\GoogleLoginRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class GoogleLoginController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
    public function __invoke(GoogleLoginRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new GoogleLoginCommand(
            idToken: $request->input('id_token'),
            userAgent: $request->input('user_agent'),
        ));
        Auth::guard('stateful-api')->loginUsingId($user->id);
        session()->regenerate();
        return ApiResponseFactory::ok(new LoginResource($user));
    }
}
