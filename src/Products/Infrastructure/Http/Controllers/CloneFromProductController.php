<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\CloneFromProduct\CloneFromProductCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class CloneFromProductController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/products/{id}/clone-from/{sourceId}',
        summary: 'Clone configuration from another product',
        operationId: 'cloneFromProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sourceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product cloned', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Products must have same type'),
        ],
    )]
    public function __invoke(Request $request, int $id, int $sourceId): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new CloneFromProductCommand(
            targetId: $id,
            sourceId: $sourceId,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
