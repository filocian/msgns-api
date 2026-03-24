<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Contracts\Controller;
use App\Http\Requests\Products\CreateProductTypeRequest;
use App\Http\Requests\Products\UpdateProductTypeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
