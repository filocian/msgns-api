<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\GroupProducts\GroupProductsCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class GroupProductsController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/products/{referenceId}/group/{candidateId}',
        summary: 'Group two compatible products',
        operationId: 'groupProducts',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'referenceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'candidateId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Products grouped', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Invalid grouping combination'),
        ],
    )]
    public function __invoke(Request $request, int $referenceId, int $candidateId): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new GroupProductsCommand(
            referenceId: $referenceId,
            candidateId: $candidateId,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
