<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\SoftRemoveProduct\SoftRemoveProductCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class SoftDeleteProductController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Delete(
        path: '/products/{id}',
        summary: 'Soft delete a product',
        operationId: 'softDeleteProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Product soft deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
        ],
    )]
    public function __invoke(Request $request, int $id): Response
    {
        unset($request);

        $this->commandBus->dispatch(new SoftRemoveProductCommand(
            productId: $id,
        ));

        return ApiResponseFactory::noContent();
    }
}
