<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Products\Application\Queries\GetPublicProduct\GetPublicProductQuery;
use Src\Products\Application\Resources\PublicProductResource;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class GetPublicProductController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/products/{id}/{password}',
        summary: 'Get public product details',
        description: 'Returns public product information by ID and password. No authentication required.',
        operationId: 'getPublicProduct',
        tags: ['Products'],
        security: [],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Product ID'),
            new OA\Parameter(name: 'password', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'Product password'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product details',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'My Product'),
                        new OA\Property(property: 'model', type: 'string', example: 'google'),
                        new OA\Property(property: 'active', type: 'boolean', example: true),
                        new OA\Property(property: 'configurationStatus', type: 'string', example: 'completed'),
                        new OA\Property(property: 'targetUrl', type: 'string', nullable: true, example: 'https://example.com'),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'assignedAt', type: 'string', nullable: true),
                        new OA\Property(property: 'size', type: 'string', nullable: true),
                        new OA\Property(property: 'productType', properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'code', type: 'string'),
                            new OA\Property(property: 'primaryModel', type: 'string'),
                        ], type: 'object'),
                    ], type: 'object'),
                ]),
            ),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
        ],
    )]
    public function __invoke(Request $request, int $id, string $password): JsonResponse
    {
        /** @var PublicProductResource $resource */
        $resource = $this->queryBus->dispatch(new GetPublicProductQuery(
            productId: $id,
            password: $password,
        ));

        return ApiResponseFactory::ok($resource->toArray());
    }
}
