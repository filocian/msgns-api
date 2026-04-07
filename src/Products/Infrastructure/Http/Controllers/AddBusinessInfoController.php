<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\AddBusinessInfo\AddBusinessInfoCommand;
use Src\Products\Infrastructure\Http\Requests\AddBusinessInfoRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AddBusinessInfoController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/products/{id}/business',
        summary: 'Add or update business information',
        operationId: 'addBusinessInfo',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['types'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150, nullable: true),
                    new OA\Property(property: 'not_a_business', type: 'boolean', nullable: true),
                    new OA\Property(property: 'types', type: 'object'),
                    new OA\Property(property: 'place_types', type: 'object', nullable: true),
                    new OA\Property(property: 'size', type: 'string', maxLength: 50, nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Business information saved', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function __invoke(AddBusinessInfoRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        assert($user !== null);

        $product = $this->commandBus->dispatch(new AddBusinessInfoCommand(
            productId: $id,
            userId: (int) $user->id,
            notABusiness: (bool) ($validated['not_a_business'] ?? false),
            types: (array) $validated['types'],
            name: isset($validated['name']) ? (string) $validated['name'] : null,
            placeTypes: isset($validated['place_types']) ? (array) $validated['place_types'] : null,
            size: isset($validated['size']) ? (string) $validated['size'] : null,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
