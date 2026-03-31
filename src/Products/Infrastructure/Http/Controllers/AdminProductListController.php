<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Products\Application\Queries\ListAdminProducts\ListAdminProductsQuery;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Tag(name: 'Products - Admin', description: 'Administration endpoints for product listings')]
final class AdminProductListController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/products/admin/',
        summary: 'List all products for the administration panel',
        description: 'Returns a paginated list of primary products across all users with filtering and sorting support for developer and backoffice roles.',
        operationId: 'listAdminProducts',
        tags: ['Products - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', default: 'assigned_at', enum: ['name', 'usage', 'active', 'configuration_status', 'model', 'assigned_at', 'created_at'])),
            new OA\Parameter(name: 'sort_dir', in: 'query', schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'product_type_code', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'product_type_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'model', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'name', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'user_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'user_email', in: 'query', schema: new OA\Schema(type: 'string', format: 'email')),
            new OA\Parameter(name: 'assigned_at_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'assigned_at_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'configuration_status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['not-started', 'assigned', 'target-set', 'business-set', 'completed'])),
            new OA\Parameter(name: 'active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'target_url', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'business_type', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'business_size', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated admin product list',
                content: new OA\JsonContent(properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 42),
                                new OA\Property(property: 'name', type: 'string', example: 'My NFC Card'),
                                new OA\Property(property: 'model', type: 'string', example: 'nfc'),
                                new OA\Property(property: 'active', type: 'boolean', example: true),
                                new OA\Property(property: 'configuration_status', type: 'string', example: 'completed'),
                                new OA\Property(property: 'usage', type: 'integer', example: 157),
                                new OA\Property(property: 'target_url', type: 'string', nullable: true, example: 'https://example.com/profile'),
                                new OA\Property(property: 'assigned_at', type: 'string', format: 'date-time', nullable: true, example: '2025-10-15T14:30:00+00:00'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true, example: '2025-09-01T10:00:00+00:00'),
                                new OA\Property(property: 'product_type', type: 'object', properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'code', type: 'string', example: 'nfc-card'),
                                    new OA\Property(property: 'name', type: 'string', example: 'NFC Card'),
                                ]),
                                new OA\Property(property: 'business', nullable: true, type: 'object', properties: [
                                    new OA\Property(property: 'types', type: 'object', example: ['restaurant' => true]),
                                    new OA\Property(property: 'size', type: 'string', nullable: true, example: 'small'),
                                ]),
                                new OA\Property(property: 'paired_product', nullable: true, type: 'object', properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 43),
                                    new OA\Property(property: 'name', type: 'string', example: 'Paired NFC'),
                                    new OA\Property(property: 'model', type: 'string', example: 'nfc'),
                                ]),
                                new OA\Property(property: 'user', nullable: true, type: 'object', properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 10),
                                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                                ]),
                            ],
                        ),
                    ),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginatedMeta'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var PaginatedResult $result */
        $result = $this->queryBus->dispatch(new ListAdminProductsQuery(
            page: max(1, (int) $request->input('page', 1)),
            perPage: min(100, max(1, (int) $request->input('per_page', 15))),
            sortBy: (string) $request->input('sort_by', 'assigned_at'),
            sortDir: (string) $request->input('sort_dir', 'desc'),
            productTypeCode: $this->nullableString($request, 'product_type_code'),
            productTypeId: $this->nullableInt($request, 'product_type_id'),
            model: $this->nullableString($request, 'model'),
            name: $this->nullableString($request, 'name'),
            userId: $this->nullableInt($request, 'user_id'),
            userEmail: $this->nullableString($request, 'user_email'),
            assignedAtFrom: $this->nullableString($request, 'assigned_at_from'),
            assignedAtTo: $this->nullableString($request, 'assigned_at_to'),
            configurationStatus: $this->nullableString($request, 'configuration_status'),
            active: $this->nullableBool($request, 'active'),
            targetUrl: $this->nullableString($request, 'target_url'),
            businessType: $this->nullableString($request, 'business_type'),
            businessSize: $this->nullableString($request, 'business_size'),
        ));

        return ApiResponseFactory::paginated($result);
    }

    private function nullableString(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(Request $request, string $key): ?int
    {
        $value = $request->input($key);

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableBool(Request $request, string $key): ?bool
    {
        if (!$request->exists($key)) {
            return null;
        }

        $value = $request->input($key);

        if ($value === '') {
            return null;
        }

        return $request->boolean($key);
    }
}
