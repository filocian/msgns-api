<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\ChangeConfigStatus\ChangeConfigStatusCommand;
use Src\Products\Infrastructure\Http\Requests\ChangeConfigStatusRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ChangeConfigStatusController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Patch(
        path: '/products/{id}/config-status',
        summary: 'Change product configuration status',
        operationId: 'changeProductConfigurationStatus',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [new OA\Property(property: 'status', type: 'string', example: 'assigned')],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Configuration status updated', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function __invoke(ChangeConfigStatusRequest $request, int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new ChangeConfigStatusCommand(
            productId: $id,
            status: (string) $request->validated('status'),
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
