<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Products\Application\Queries\ListUserProducts\ListUserProductsQuery;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class UserProductController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/products/',
        summary: 'List authenticated user\'s products',
        operationId: 'listUserProducts',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(
                name: 'sort_by',
                in: 'query',
                schema: new OA\Schema(
                    type: 'string',
                    default: 'assigned_at',
                    enum: ['name', 'usage', 'active', 'configuration_status', 'model', 'assigned_at'],
                ),
            ),
            new OA\Parameter(
                name: 'sort_dir',
                in: 'query',
                schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc']),
            ),
            new OA\Parameter(
                name: 'configuration_status',
                in: 'query',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['not-started', 'assigned', 'target-set', 'business-set', 'completed'],
                ),
            ),
            new OA\Parameter(name: 'active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'model', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'target_url', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'has_business_info', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated product list',
                content: new OA\JsonContent(properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 42),
                            new OA\Property(property: 'name', type: 'string', example: 'My NFC Card'),
                            new OA\Property(property: 'model', type: 'string', example: 'nfc'),
                            new OA\Property(property: 'active', type: 'boolean', example: true),
                            new OA\Property(property: 'configuration_status', type: 'string', example: 'completed'),
                            new OA\Property(property: 'usage', type: 'integer', example: 157),
                            new OA\Property(property: 'target_url', type: 'string', nullable: true, example: 'https://example.com/profile'),
                            new OA\Property(property: 'assigned_at', type: 'string', format: 'date-time', nullable: true, example: '2025-10-15T14:30:00+00:00'),
                            new OA\Property(
                                property: 'product_type',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'code', type: 'string', example: 'nfc-card'),
                                    new OA\Property(property: 'name', type: 'string', example: 'NFC Card'),
                                ],
                                type: 'object',
                            ),
                            new OA\Property(
                                property: 'business',
                                nullable: true,
                                properties: [
                                    new OA\Property(property: 'types', type: 'object', example: ['restaurant' => true]),
                                    new OA\Property(property: 'size', type: 'string', nullable: true, example: 'small'),
                                ],
                                type: 'object',
                            ),
                        ], type: 'object'),
                    ),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginatedMeta'),
                ]),
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/DomainError'),
            ),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var PaginatedResult $result */
        $result = $this->queryBus->dispatch(new ListUserProductsQuery(
            userId: (int) $request->user()?->id,
            page: (int) $request->input('page', 1),
            perPage: min(100, (int) $request->input('per_page', 15)),
            sortBy: (string) $request->input('sort_by', 'assigned_at'),
            sortDir: (string) $request->input('sort_dir', 'desc'),
            configurationStatus: is_string($request->input('configuration_status'))
                ? $request->input('configuration_status')
                : null,
            active: $request->has('active') ? $request->boolean('active') : null,
            model: is_string($request->input('model')) ? $request->input('model') : null,
            targetUrl: is_string($request->input('target_url')) ? $request->input('target_url') : null,
            hasBusinessInfo: $request->has('has_business_info') ? $request->boolean('has_business_info') : null,
        ));

        return ApiResponseFactory::paginated($result);
    }
}
