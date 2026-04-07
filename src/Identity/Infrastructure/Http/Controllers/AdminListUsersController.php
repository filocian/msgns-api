<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Queries\ListUsers\ListUsersQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminListUsersController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/identity/admin/users',
        summary: 'List all users',
        description: 'Returns a paginated list of users with optional filtering and sorting.',
        operationId: 'listUsers',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', default: 'created_at')),
            new OA\Parameter(name: 'sort_dir', in: 'query', schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'role', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Users list', content: new OA\JsonContent(
                allOf: [
                    new OA\Schema(ref: '#/components/schemas/JsonEnvelope'),
                    new OA\Schema(properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/UserResource')),
                            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginatedMeta'),
                        ]),
                    ])
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - requires admin permissions'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListUsersQuery(
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 15),
            sortBy: $request->input('sort_by', 'created_at'),
            sortDir: $request->input('sort_dir', 'desc'),
            search: $request->input('search'),
            active: $request->has('active') ? (bool) $request->input('active') : null,
            role: $request->input('role'),
        ));
        return ApiResponseFactory::paginated($result);
    }
}
