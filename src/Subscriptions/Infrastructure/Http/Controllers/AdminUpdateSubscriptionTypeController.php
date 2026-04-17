<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Src\Subscriptions\Application\Commands\UpdateSubscriptionType\UpdateSubscriptionTypeCommand;
use Src\Subscriptions\Infrastructure\Http\Requests\UpdateSubscriptionTypeRequest;

final class AdminUpdateSubscriptionTypeController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Put(
        path: '/subscriptions/admin/subscription-types/{id}',
        summary: 'Update mutable fields of a subscription type',
        description: 'Updates name, description, permission, and per-feature limits. Stripe binding (product/prices), mode, base price and billing periods are immutable after creation.',
        operationId: 'updateSubscriptionType',
        tags: ['Subscriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'permission_name', 'google_review_limit', 'instagram_content_limit'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Google Review Basic'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: null),
                    new OA\Property(property: 'permission_name', type: 'string', example: 'ai.google-review-basic'),
                    new OA\Property(property: 'google_review_limit', type: 'integer', example: 50),
                    new OA\Property(property: 'instagram_content_limit', type: 'integer', example: 0),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Subscription type updated', content: new OA\JsonContent(ref: '#/components/schemas/SubscriptionTypeResource')),
            new OA\Response(response: 400, description: 'Validation error (including prohibited fields: stripe_product_id, mode, base_price_cents, billing_periods)'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function __invoke(UpdateSubscriptionTypeRequest $request, int $id): JsonResponse
    {
        $result = $this->commandBus->dispatch(new UpdateSubscriptionTypeCommand(
            id: $id,
            name: (string) $request->validated('name'),
            description: $request->validated('description'),
            permissionName: (string) $request->validated('permission_name'),
            googleReviewLimit: (int) $request->validated('google_review_limit'),
            instagramContentLimit: (int) $request->validated('instagram_content_limit'),
        ));

        return ApiResponseFactory::ok($result);
    }
}
