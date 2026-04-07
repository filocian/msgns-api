<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\CreateProductType\CreateProductTypeCommand;
use Src\Products\Infrastructure\Http\Requests\CreateProductTypeRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class CreateProductTypeController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Product type created', content: new OA\JsonContent(ref: '#/components/schemas/ProductTypeResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function __invoke(CreateProductTypeRequest $request): JsonResponse
    {
        $productType = $this->commandBus->dispatch(new CreateProductTypeCommand(
            code: $request->validated('code'),
            name: $request->validated('name'),
            primaryModel: $request->validated('primary_model'),
            secondaryModel: $request->validated('secondary_model'),
        ));

        return ApiResponseFactory::created($productType);
    }
}
