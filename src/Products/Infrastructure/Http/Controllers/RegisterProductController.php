<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\RegisterProduct\RegisterProductCommand;
use Src\Products\Infrastructure\Http\Requests\RegisterProductRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class RegisterProductController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/products/{id}/register',
        summary: 'Register product with password and owner',
        operationId: 'registerProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'password'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 7),
                    new OA\Property(property: 'password', type: 'string', example: 'my-secret'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product registered', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error or invalid password'),
        ],
    )]
    public function __invoke(RegisterProductRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        assert($user !== null);

        $product = $this->commandBus->dispatch(new RegisterProductCommand(
            productId: $id,
            userId: (int) $request->validated('user_id'),
            password: (string) $request->validated('password'),
            actorUserId: (int) $user->id,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
