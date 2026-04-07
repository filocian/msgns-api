<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\DeactivateProduct\DeactivateProductCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class DeactivateProductController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/products/{id}/deactivate',
        summary: 'Deactivate a product',
        operationId: 'deactivateProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product deactivated', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
        ],
    )]
    public function __invoke(Request $request, int $id): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new DeactivateProductCommand(
            productId: $id,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
