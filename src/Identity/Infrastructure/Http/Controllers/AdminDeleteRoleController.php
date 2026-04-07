<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\DeleteRole\DeleteRoleCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminDeleteRoleController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Delete(
        path: '/identity/admin/roles/{id}',
        summary: 'Delete a role',
        operationId: 'deleteRole',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Role deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Role not found'),
        ]
    )]
    public function __invoke(int $id): Response
    {
        $this->commandBus->dispatch(new DeleteRoleCommand(id: $id));
        return ApiResponseFactory::noContent();
    }
}
