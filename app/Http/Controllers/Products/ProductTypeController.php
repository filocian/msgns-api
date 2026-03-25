<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Contracts\Controller;
use App\Http\OpenApi\Schemas as OpenApiSchemas;
use App\Http\Requests\Products\CreateProductTypeRequest;
use App\Http\Requests\Products\UpdateProductTypeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\CreateProductType\CreateProductTypeCommand;
use Src\Products\Application\Commands\UpdateProductType\UpdateProductTypeCommand;
use Src\Products\Application\Queries\GetProductType\GetProductTypeQuery;
use Src\Products\Application\Queries\ListProductTypes\ListProductTypesQuery;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

/**
 * Thin HTTP adapter for the Products module — Product Types resource.
 *
 * Each method follows the single responsibility:
 *   1. Receive (and validate via FormRequest)
 *   2. Build and dispatch the Command or Query through the appropriate Bus
 *   3. Return a structured JSON response via ApiResponseFactory
 *
 * No business logic lives here.
 */
#[OA\Tag(name: 'Products', description: 'Product type management endpoints')]
final class ProductTypeController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    /**
     * GET /api/v2/products/product-types
     *
     * Returns a paginated list of Product Types.
     */
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
                    ])
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListProductTypesQuery(
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 15),
            sortBy: $request->input('sort_by', 'name'),
            sortDir: $request->input('sort_dir', 'asc'),
        ));

        return ApiResponseFactory::paginated($result);
    }

    /**
     * GET /api/v2/products/product-types/{id}
     *
     * Returns the detail of a single Product Type.
     * Throws NotFound (404) when the id does not match any record.
     */
    #[OA\Get(
        path: '/products/product-types/{id}',
        summary: 'Get a product type',
        description: 'Returns the details of a specific product type by ID.',
        operationId: 'getProductType',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Product type ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product type details', content: new OA\JsonContent(ref: '#/components/schemas/ProductTypeResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Product type not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $productType = $this->queryBus->dispatch(new GetProductTypeQuery(
            productTypeId: $id,
        ));

        return ApiResponseFactory::ok($productType);
    }

    /**
     * POST /api/v2/products/product-types
     *
     * Creates a new Product Type and returns the persisted resource (201).
     */
    #[OA\Post(
        path: '/products/product-types',
        summary: 'Create a product type',
        description: 'Creates a new product type with the provided data.',
        operationId: 'createProductType',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'name', 'primary_model'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'fan', description: 'Unique code for the product type'),
                    new OA\Property(property: 'name', type: 'string', example: 'Fan'),
                    new OA\Property(property: 'primary_model', type: 'string', example: 'fan001', description: 'Primary model identifier'),
                    new OA\Property(property: 'secondary_model', type: 'string', nullable: true, example: 'fan001b', description: 'Optional secondary model'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Product type created', content: new OA\JsonContent(ref: '#/components/schemas/ProductTypeResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(CreateProductTypeRequest $request): JsonResponse
    {
        $productType = $this->commandBus->dispatch(new CreateProductTypeCommand(
            code: $request->validated('code'),
            name: $request->validated('name'),
            primaryModel: $request->validated('primary_model'),
            secondaryModel: $request->validated('secondary_model'),
        ));

        return ApiResponseFactory::created($productType);
    }

    /**
     * PATCH /api/v2/products/product-types/{id}
     *
     * Partially updates a Product Type.
     * Protected fields (`code`, `primary_model`, `secondary_model`) are rejected
     * by the domain when the type is already in use (422 ValidationFailed).
     */
    #[OA\Patch(
        path: '/products/product-types/{id}',
        summary: 'Update a product type',
        description: 'Partially updates a product type. Some fields are protected when the type is in use.',
        operationId: 'updateProductType',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Product type ID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'code', type: 'string', description: 'Unique code (protected if type is in use)'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'primary_model', type: 'string', description: 'Primary model (protected if type is in use)'),
                    new OA\Property(property: 'secondary_model', type: 'string', nullable: true, description: 'Secondary model (protected if type is in use)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product type updated', content: new OA\JsonContent(ref: '#/components/schemas/ProductTypeResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Product type not found'),
            new OA\Response(response: 422, description: 'Validation error - field protected or invalid'),
        ]
    )]
    public function update(UpdateProductTypeRequest $request, int $id): JsonResponse
    {
        $productType = $this->commandBus->dispatch(new UpdateProductTypeCommand(
            productTypeId: $id,
            code: $request->validated('code'),
            name: $request->validated('name'),
            primaryModel: $request->validated('primary_model'),
            secondaryModel: $request->validated('secondary_model'),
        ));

        return ApiResponseFactory::ok($productType);
    }
}
