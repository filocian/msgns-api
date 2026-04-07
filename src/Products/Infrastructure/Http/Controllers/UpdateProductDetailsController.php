<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\UpdateProductDetails\UpdateProductDetailsCommand;
use Src\Products\Infrastructure\Http\Requests\UpdateProductDetailsRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class UpdateProductDetailsController
{
	public function __construct(
		private readonly CommandBus $commandBus,
	) {}

	#[OA\Patch(
		path: '/products/{id}/details',
		summary: 'Partially update product details',
		operationId: 'updateProductDetails',
		tags: ['Products'],
		security: [['bearerAuth' => []]],
		parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
		requestBody: new OA\RequestBody(
			required: true,
			content: new OA\JsonContent(
				minProperties: 1,
				properties: [
					new OA\Property(property: 'name', type: 'string', maxLength: 255, nullable: false),
					new OA\Property(property: 'description', type: 'string', maxLength: 500, nullable: true),
				],
			),
		),
		responses: [
			new OA\Response(response: 200, description: 'Product details updated', content: new OA\JsonContent(
				ref: '#/components/schemas/ProductEnvelope'
			)),
			new OA\Response(response: 401, description: 'Unauthenticated'),
			new OA\Response(response: 403, description: 'Forbidden'),
			new OA\Response(response: 404, description: 'Product not found'),
			new OA\Response(response: 422, description: 'Validation error'),
		],
	)]
	public function __invoke(UpdateProductDetailsRequest $request, int $id): JsonResponse
	{
		$validated = $request->validated();
		$user = $request->user();
		assert($user !== null);

		$product = $this->commandBus->dispatch(new UpdateProductDetailsCommand(
			productId: $id,
			actorUserId: (int) $user->id,
			name: array_key_exists('name', $validated) ? (string) $validated['name'] : null,
			description: array_key_exists(
				'description',
				$validated
			) ? ($validated['description'] !== null ? (string) $validated['description'] : null) : null,
			hasName: array_key_exists('name', $validated),
			hasDescription: array_key_exists('description', $validated),
		));

		return ApiResponseFactory::ok(['product' => $product]);
	}
}
