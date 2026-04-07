<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\RemoveRole\RemoveRoleCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminRemoveRoleFromUserController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Delete(
        path: '/identity/admin/users/{id}/roles/{role}',
        summary: 'Remove a role from a user',
        operationId: 'removeRole',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Role removed'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User or role not found'),
        ]
    )]
    public function __invoke(int $id, string $role): Response
    {
        $this->commandBus->dispatch(new RemoveRoleCommand(
            userId: $id,
            role: $role,
        ));
        return ApiResponseFactory::noContent();
    }
}
