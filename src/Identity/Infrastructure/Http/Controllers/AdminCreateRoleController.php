<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\CreateRole\CreateRoleCommand;
use Src\Identity\Infrastructure\Http\Requests\AdminCreateRoleRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminCreateRoleController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
    public function __invoke(AdminCreateRoleRequest $request): JsonResponse
    {
        $role = $this->commandBus->dispatch(new CreateRoleCommand(
            name: $request->input('name'),
        ));
        return ApiResponseFactory::created($role);
    }
}
