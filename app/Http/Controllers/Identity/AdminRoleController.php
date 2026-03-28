<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Http\Contracts\Controller;
use App\Http\OpenApi\Schemas as OpenApiSchemas;
use App\Http\Requests\Identity\AdminAssignRoleRequest;
use App\Http\Requests\Identity\AdminCreateRoleRequest;
use App\Http\Requests\Identity\AdminUpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\AssignRole\AssignRoleCommand;
use Src\Identity\Application\Commands\CreateRole\CreateRoleCommand;
use Src\Identity\Application\Commands\DeleteRole\DeleteRoleCommand;
use Src\Identity\Application\Commands\RemoveRole\RemoveRoleCommand;
use Src\Identity\Application\Commands\UpdateRole\UpdateRoleCommand;
use Src\Identity\Application\Queries\ListRoles\ListRolesQuery;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Tag(name: 'Identity - Admin', description: 'Administrator endpoints for role management')]
final class AdminRoleController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/identity/admin/roles',
        summary: 'List all roles',
        operationId: 'listRoles',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Roles list', content: new OA\JsonContent(
                allOf: [
                    new OA\Schema(ref: '#/components/schemas/JsonEnvelope'),
                    new OA\Schema(properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RoleResource')),
                    ])
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(): JsonResponse
    {
        $roles = $this->queryBus->dispatch(new ListRolesQuery());
        return ApiResponseFactory::ok($roles);
    }

    #[OA\Post(
        path: '/identity/admin/roles',
        summary: 'Create a new role',
        operationId: 'createRole',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'editor'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Role created', content: new OA\JsonContent(ref: '#/components/schemas/RoleResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(AdminCreateRoleRequest $request): JsonResponse
    {
        $role = $this->commandBus->dispatch(new CreateRoleCommand(
            name: $request->input('name'),
        ));
        return ApiResponseFactory::created($role);
    }

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
    public function update(AdminUpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->commandBus->dispatch(new UpdateRoleCommand(
            id: $id,
            name: $request->input('name'),
        ));
        return ApiResponseFactory::ok($role);
    }

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
    public function destroy(int $id): Response
    {
        $this->commandBus->dispatch(new DeleteRoleCommand(id: $id));
        return ApiResponseFactory::noContent();
    }

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
    public function assignToUser(AdminAssignRoleRequest $request, int $id): Response
    {
        $this->commandBus->dispatch(new AssignRoleCommand(
            userId: $id,
            role: $request->input('role'),
        ));
        return ApiResponseFactory::noContent();
    }

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
    public function removeFromUser(int $id, string $role): Response
    {
        $this->commandBus->dispatch(new RemoveRoleCommand(
            userId: $id,
            role: $role,
        ));
        return ApiResponseFactory::noContent();
    }
}
