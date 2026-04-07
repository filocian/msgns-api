<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\AssignToUser\AssignToUserCommand;
use Src\Products\Infrastructure\Http\Requests\AssignToUserRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AssignToUserController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Patch(
        path: '/products/{id}/assign',
        summary: 'Assign product to a user',
        operationId: 'assignProductToUser',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 7),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product assigned', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function __invoke(AssignToUserRequest $request, int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new AssignToUserCommand(
            productId: $id,
            userId: (int) $request->validated('user_id'),
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
