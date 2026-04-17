<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Src\Subscriptions\Application\Commands\CreateSubscriptionType\CreateSubscriptionTypeCommand;
use Src\Subscriptions\Infrastructure\Http\Requests\CreateSubscriptionTypeRequest;

final class AdminCreateSubscriptionTypeController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/subscriptions/admin/subscription-types',
        summary: 'Create a subscription type bound to a Stripe product',
        description: 'Creates a new subscription type from a Stripe product. Mode, billing periods, base price and stripe_price_ids are derived server-side from the Stripe catalog. EUR-only.',
        operationId: 'createSubscriptionType',
        tags: ['Subscriptions'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'permission_name', 'google_review_limit', 'instagram_content_limit', 'stripe_product_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Google Review Basic'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Basic plan for Google Reviews'),
                    new OA\Property(property: 'permission_name', type: 'string', example: 'ai.google-review-basic'),
                    new OA\Property(property: 'google_review_limit', type: 'integer', example: 50),
                    new OA\Property(property: 'instagram_content_limit', type: 'integer', example: 0),
                    new OA\Property(property: 'stripe_product_id', type: 'string', pattern: '^prod_[A-Za-z0-9]+$', example: 'prod_abc123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Subscription type created', content: new OA\JsonContent(ref: '#/components/schemas/SubscriptionTypeResource')),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Domain error (Stripe product not found, mixed prices, invalid currency, duplicate binding, etc.)'),
        ],
    )]
    public function __invoke(CreateSubscriptionTypeRequest $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new CreateSubscriptionTypeCommand(
            name: (string) $request->validated('name'),
            description: $request->validated('description'),
            permissionName: (string) $request->validated('permission_name'),
            googleReviewLimit: (int) $request->validated('google_review_limit'),
            instagramContentLimit: (int) $request->validated('instagram_content_limit'),
            stripeProductId: (string) $request->validated('stripe_product_id'),
        ));

        return ApiResponseFactory::created($result);
    }
}
