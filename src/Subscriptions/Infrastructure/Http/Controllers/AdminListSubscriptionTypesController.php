<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Src\Subscriptions\Application\Queries\ListAdminSubscriptionTypes\ListAdminSubscriptionTypesQuery;
use Src\Subscriptions\Infrastructure\Http\Requests\ListAdminSubscriptionTypesRequest;

final class AdminListSubscriptionTypesController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/subscriptions/admin/subscription-types',
        summary: 'List subscription types (admin)',
        description: 'Returns a paginated list of subscription types with optional filtering.',
        operationId: 'listAdminSubscriptionTypes',
        tags: ['Subscriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', default: 'name')),
            new OA\Parameter(name: 'sort_dir', in: 'query', schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'mode', in: 'query', schema: new OA\Schema(type: 'string', enum: ['classic', 'prepaid'])),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated subscription types list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function __invoke(ListAdminSubscriptionTypesRequest $request): JsonResponse
    {
        $isActive = $request->has('is_active') ? (bool) $request->input('is_active') : null;

        $result = $this->queryBus->dispatch(new ListAdminSubscriptionTypesQuery(
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 15),
            sortBy: (string) $request->input('sort_by', 'name'),
            sortDir: (string) $request->input('sort_dir', 'asc'),
            mode: $request->input('mode'),
            isActive: $isActive,
        ));

        return ApiResponseFactory::paginated($result);
    }
}
