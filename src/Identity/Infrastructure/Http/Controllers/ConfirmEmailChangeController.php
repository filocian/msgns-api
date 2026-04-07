<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\ConfirmEmailChange\ConfirmEmailChangeCommand;
use Src\Identity\Infrastructure\Http\Requests\ConfirmEmailChangeRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ConfirmEmailChangeController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
    public function __invoke(ConfirmEmailChangeRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new ConfirmEmailChangeCommand(
            token: $request->input('token'),
        ));
        return ApiResponseFactory::ok($user);
    }
}
