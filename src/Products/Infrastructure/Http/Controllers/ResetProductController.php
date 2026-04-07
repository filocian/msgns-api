<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\ResetProduct\ResetProductCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ResetProductController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/products/{id}/reset',
        summary: 'Reset a product to virgin state',
        operationId: 'resetProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product reset to virgin state', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Product type cannot be reset (bracelet/fancelet types)'),
        ],
    )]
    public function __invoke(Request $request, int $id): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new ResetProductCommand(
            productId: $id,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
