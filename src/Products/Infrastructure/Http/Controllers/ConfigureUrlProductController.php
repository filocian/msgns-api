<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\ConfigureUrlProduct\ConfigureUrlProductCommand;
use Src\Products\Infrastructure\Http\Requests\ConfigureUrlProductRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ConfigureUrlProductController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Put(
        path: '/products/{id}/configure',
        summary: 'Configure product target URL with forward-only status transition',
        operationId: 'configureUrlProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['target_url'],
                properties: [new OA\Property(property: 'target_url', type: 'string', format: 'uri', maxLength: 2048)],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product configured', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function __invoke(ConfigureUrlProductRequest $request, int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new ConfigureUrlProductCommand(
            productId: $id,
            targetUrl: (string) $request->validated('target_url'),
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
