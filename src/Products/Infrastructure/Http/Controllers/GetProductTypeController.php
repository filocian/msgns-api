<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Queries\GetProductType\GetProductTypeQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class GetProductTypeController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

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
        ],
    )]
    public function __invoke(int $id): JsonResponse
    {
        $productType = $this->queryBus->dispatch(new GetProductTypeQuery(
            productTypeId: $id,
        ));

        return ApiResponseFactory::ok($productType);
    }
}
