<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\AdminActivateUser\AdminActivateUserCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminActivateUserController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Patch(
        path: '/identity/admin/users/{id}/activate',
        summary: 'Activate a user',
        operationId: 'activateUser',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User activated', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function __invoke(int $id): JsonResponse
    {
        $user = $this->commandBus->dispatch(new AdminActivateUserCommand(
            userId: $id,
            activatedBy: (int) Auth::id(),
        ));
        return ApiResponseFactory::ok($user);
    }
}
