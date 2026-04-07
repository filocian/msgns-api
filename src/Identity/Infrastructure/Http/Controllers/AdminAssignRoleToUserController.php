<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\AssignRole\AssignRoleCommand;
use Src\Identity\Infrastructure\Http\Requests\AdminAssignRoleRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminAssignRoleToUserController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/identity/admin/users/{id}/roles',
        summary: 'Assign a role to a user',
        operationId: 'assignRole',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role'],
                properties: [
                    new OA\Property(property: 'role', type: 'string', example: 'admin'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Role assigned'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function __invoke(AdminAssignRoleRequest $request, int $id): Response
    {
        $this->commandBus->dispatch(new AssignRoleCommand(
            userId: $id,
            role: $request->input('role'),
        ));
        return ApiResponseFactory::noContent();
    }
}
