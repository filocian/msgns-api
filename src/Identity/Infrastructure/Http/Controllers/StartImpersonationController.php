<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\StartImpersonation\StartImpersonationCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class StartImpersonationController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/identity/impersonate/{id}',
        summary: 'Start impersonating a user',
        description: 'Allows an administrator to impersonate another user. The response includes tokens to switch context.',
        operationId: 'startImpersonation',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'User ID to impersonate'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Impersonation started', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'impersonation_token', type: 'string'),
                        new OA\Property(property: 'original_token', type: 'string'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                    ])
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - requires admin permissions'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $adminUserId = (int) Auth::id();
        $result = $this->commandBus->dispatch(new StartImpersonationCommand(
            adminUserId: $adminUserId,
            targetUserId: $id,
        ));
        return ApiResponseFactory::ok($result);
    }
}
