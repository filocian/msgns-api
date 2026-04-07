<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Queries\ListGenerationHistory\ListGenerationHistoryQuery;
use Src\Products\Infrastructure\Http\Requests\ListGenerationHistoryRequest;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ListGenerationHistoryController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/products/generations',
        summary: 'List product generation history',
        operationId: 'listGenerationHistory',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 50)),
            new OA\Parameter(name: 'timezone', in: 'query', schema: new OA\Schema(type: 'string', default: 'UTC')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Generation history list', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GenerationHistoryListItem')),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginatedMeta'),
                ],
                type: 'object',
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function __invoke(ListGenerationHistoryRequest $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListGenerationHistoryQuery(
            page: (int) $request->validated('page', 1),
            perPage: (int) $request->validated('per_page', 15),
            timezone: (string) $request->validated('timezone', 'UTC'),
        ));

        return ApiResponseFactory::paginated($result);
    }
}
