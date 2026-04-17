<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Ai\Application\Commands\CancelClassicSubscription\CancelClassicSubscriptionCommand;
use Src\Ai\Application\Commands\SubscribeToClassicPlan\SubscribeToClassicPlanCommand;
use Src\Ai\Application\Queries\GetActiveClassicSubscription\GetActiveClassicSubscriptionQuery;
use Src\Ai\Infrastructure\Http\Requests\SubscribeToClassicPlanRequest;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

#[OA\Schema(
    schema: 'ClassicSubscriptionResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'billing_period', type: 'string', enum: ['monthly', 'annual']),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'cancelled', 'expired']),
        new OA\Property(property: 'current_period_start', type: 'string', format: 'date-time'),
        new OA\Property(property: 'current_period_end', type: 'string', format: 'date-time'),
        new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'subscription_type', ref: '#/components/schemas/SubscriptionTypeResource'),
    ],
    required: ['id', 'billing_period', 'status', 'current_period_start', 'current_period_end', 'cancelled_at'],
)]
final class ClassicSubscriptionController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/ai/subscriptions/classic',
        summary: 'Subscribe to a classic AI plan',
        description: 'Creates a recurring Stripe subscription for the authenticated user and grants the corresponding permission.',
        operationId: 'subscribeToClassicPlan',
        tags: ['AI Subscriptions'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'subscription_type_id', type: 'integer', description: 'ID of the subscription type to subscribe to'),
                    new OA\Property(property: 'billing_period', type: 'string', enum: ['monthly', 'annual'], description: 'Billing period'),
                    new OA\Property(property: 'payment_method_id', type: 'string', description: 'Stripe Payment Method ID (pm_...)'),
                ],
                required: ['subscription_type_id', 'billing_period', 'payment_method_id'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Subscription created',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/ClassicSubscriptionResource')],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 402,
                description: 'Payment requires 3DS authentication — includes client_secret for Stripe.js confirmCardPayment()',
                content: new OA\JsonContent(ref: '#/components/schemas/DomainError'),
            ),
            new OA\Response(response: 409, description: 'User already has an active subscription', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function subscribe(SubscribeToClassicPlanRequest $request): JsonResponse
    {
        /** @var UserSubscriptionModel $subscription */
        $subscription = $this->commandBus->dispatch(new SubscribeToClassicPlanCommand(
            userId: (int) Auth::id(),
            subscriptionTypeId: (int) $request->validated('subscription_type_id'),
            billingPeriod: (string) $request->validated('billing_period'),
            paymentMethodId: (string) $request->validated('payment_method_id'),
        ));

        $subscription->load('subscriptionType');

        return ApiResponseFactory::created($this->formatSubscription($subscription));
    }

    #[OA\Delete(
        path: '/ai/subscriptions/classic',
        summary: 'Cancel classic AI subscription',
        description: 'Cancels the subscription at the end of the current period. Access is retained until period end. Permission revocation happens in BE-7 via webhook.',
        operationId: 'cancelClassicSubscription',
        tags: ['AI Subscriptions'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscription cancelled (access retained until period end)',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/ClassicSubscriptionResource')],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'No active subscription found', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function cancel(): JsonResponse
    {
        /** @var UserSubscriptionModel $subscription */
        $subscription = $this->commandBus->dispatch(new CancelClassicSubscriptionCommand(
            userId: (int) Auth::id(),
        ));

        $subscription->load('subscriptionType');

        return ApiResponseFactory::ok($this->formatSubscription($subscription));
    }

    #[OA\Get(
        path: '/ai/subscriptions/classic',
        summary: 'Get active classic AI subscription',
        description: 'Returns the active or cancelled (still-accessible) classic subscription for the authenticated user.',
        operationId: 'getActiveClassicSubscription',
        tags: ['AI Subscriptions'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Active or cancelled subscription',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/ClassicSubscriptionResource')],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'No subscription found', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function show(): JsonResponse
    {
        /** @var UserSubscriptionModel|null $subscription */
        $subscription = $this->queryBus->dispatch(new GetActiveClassicSubscriptionQuery(
            userId: (int) Auth::id(),
        ));

        if ($subscription === null) {
            abort(404, 'subscription_not_found');
        }

        return ApiResponseFactory::ok($this->formatSubscription($subscription));
    }

    /** @return array<string, mixed> */
    private function formatSubscription(UserSubscriptionModel $subscription): array
    {
        $type = $subscription->subscriptionType;

        return [
            'id'                   => $subscription->id,
            'billing_period'       => $subscription->billing_period,
            'status'               => $subscription->status,
            'current_period_start' => $subscription->current_period_start->toISOString(),
            'current_period_end'   => $subscription->current_period_end->toISOString(),
            'cancelled_at'         => $subscription->cancelled_at?->toISOString(),
            'subscription_type'    => [
                'id'                      => $type->id,
                'name'                    => $type->name,
                'slug'                    => $type->slug,
                'permission_name'         => $type->permission_name,
                'google_review_limit'     => $type->google_review_limit,
                'instagram_content_limit' => $type->instagram_content_limit,
            ],
        ];
    }
}
