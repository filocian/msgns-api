<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\UpdateProductType\UpdateProductTypeCommand;
use Src\Products\Infrastructure\Http\Requests\UpdateProductTypeRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class UpdateProductTypeController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product type updated', content: new OA\JsonContent(ref: '#/components/schemas/ProductTypeResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Product type not found'),
            new OA\Response(response: 422, description: 'Validation error - field protected or invalid'),
        ],
    )]
    public function __invoke(UpdateProductTypeRequest $request, int $id): JsonResponse
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
