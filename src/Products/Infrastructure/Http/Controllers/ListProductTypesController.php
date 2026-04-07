<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Products\Application\Queries\ListProductTypes\ListProductTypesQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ListProductTypesController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/products/product-types',
        summary: 'List product types',
        description: 'Returns a paginated list of all product types with optional sorting.',
        operationId: 'listProductTypes',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', default: 'name')),
            new OA\Parameter(name: 'sort_dir', in: 'query', schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product types list', content: new OA\JsonContent(
                allOf: [
                    new OA\Schema(ref: '#/components/schemas/JsonEnvelope'),
                    new OA\Schema(properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductTypeResource')),
                            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginatedMeta'),
                        ]),
                    ]),
                ],
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListProductTypesQuery(
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 15),
            sortBy: $request->input('sort_by', 'name'),
            sortDir: $request->input('sort_dir', 'asc'),
        ));

        return ApiResponseFactory::paginated($result);
    }
}
