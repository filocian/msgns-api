<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\RequestEmailChange\RequestEmailChangeCommand;
use Src\Identity\Infrastructure\Http\Requests\RequestEmailChangeRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class RequestEmailChangeController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
    public function __invoke(RequestEmailChangeRequest $request): JsonResponse
    {
        $userId = (int) Auth::id();
        $this->commandBus->dispatch(new RequestEmailChangeCommand(
            userId: $userId,
            newEmail: $request->input('new_email'),
            password: $request->input('password'),
        ));
        return ApiResponseFactory::ok(['message' => 'email_change_requested']);
    }
}
