<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\SyncRolePermissions\SyncRolePermissionsCommand;
use Src\Identity\Infrastructure\Http\Requests\SyncRolePermissionsRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminSyncRolePermissionsController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Put(
        path: '/identity/admin/roles/{id}/permissions',
        summary: 'Sync permissions for a role (full replace)',
        operationId: 'syncRolePermissions',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['permissions'],
                properties: [
                    new OA\Property(
                        property: 'permissions',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['create_role', 'edit_role', 'assign_role'],
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Permissions synced', content: new OA\JsonContent(
                allOf: [
                    new OA\Schema(ref: '#/components/schemas/JsonEnvelope'),
                    new OA\Schema(properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RoleResource'),
                    ]),
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — core role or insufficient permissions'),
            new OA\Response(response: 404, description: 'Role not found'),
            new OA\Response(response: 422, description: 'Validation error — invalid permission names or format'),
        ]
    )]
    public function __invoke(SyncRolePermissionsRequest $request, int $id): JsonResponse
    {
        $role = $this->commandBus->dispatch(new SyncRolePermissionsCommand(
            roleId: $id,
            permissions: $request->input('permissions'),
        ));

        return ApiResponseFactory::ok($role);
    }
}
