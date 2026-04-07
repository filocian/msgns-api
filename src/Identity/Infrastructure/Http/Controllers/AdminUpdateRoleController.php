<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\UpdateRole\UpdateRoleCommand;
use Src\Identity\Infrastructure\Http\Requests\AdminUpdateRoleRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminUpdateRoleController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Patch(
        path: '/identity/admin/roles/{id}',
        summary: 'Update a role',
        operationId: 'updateRole',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Role updated', content: new OA\JsonContent(ref: '#/components/schemas/RoleResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Role not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function __invoke(AdminUpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->commandBus->dispatch(new UpdateRoleCommand(
            id: $id,
            name: $request->input('name'),
        ));
        return ApiResponseFactory::ok($role);
    }
}
