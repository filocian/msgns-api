<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Queries\ListRoles\ListRolesQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminListRolesController extends Controller
{
    public function __construct(
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
    public function __invoke(): JsonResponse
    {
        $roles = $this->queryBus->dispatch(new ListRolesQuery());
        return ApiResponseFactory::ok($roles);
    }
}
