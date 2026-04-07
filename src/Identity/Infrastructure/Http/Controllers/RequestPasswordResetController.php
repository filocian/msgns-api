<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\RequestPasswordReset\RequestPasswordResetCommand;
use Src\Identity\Infrastructure\Http\Requests\RequestPasswordResetRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class RequestPasswordResetController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
    public function __invoke(RequestPasswordResetRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new RequestPasswordResetCommand(
            email: $request->input('email'),
        ));
        return ApiResponseFactory::ok(['message' => 'password_reset_requested']);
    }
}
