<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Billing\Application\Commands\ExpireSubscriptionFromStripe\ExpireSubscriptionFromStripeCommand;
use Src\Billing\Application\Commands\HandlePrepaidPaymentFailed\HandlePrepaidPaymentFailedCommand;
use Src\Billing\Application\Commands\HandlePrepaidPaymentSucceeded\HandlePrepaidPaymentSucceededCommand;
use Src\Billing\Application\Commands\SyncSubscriptionStatusFromStripe\SyncSubscriptionStatusFromStripeCommand;
use Src\Billing\Infrastructure\Persistence\StripeWebhookEventModel;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Schema(
    schema: 'WebhookAcknowledgment',
    type: 'object',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['ok', 'already_processed']),
    ],
)]
final class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/billing/stripe',
        summary: 'Stripe webhook endpoint for subscription lifecycle events',
        description: 'Handles Stripe webhook events for subscription lifecycle sync and prepaid payment processing. Idempotent — duplicate events return already_processed.',
        operationId: 'stripeWebhook',
        tags: ['Webhooks'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'object'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event acknowledged',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/WebhookAcknowledgment',
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 403, description: 'Invalid or missing Stripe signature'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true);

        $stripeEventId = $payload['id'];
        $eventType     = $payload['type'];

        try {
            $event = StripeWebhookEventModel::firstOrCreate(
                ['stripe_event_id' => $stripeEventId],
                ['event_type' => $eventType, 'payload' => $payload, 'processed_at' => null],
            );
        } catch (UniqueConstraintViolationException) {
            // Concurrent duplicate — race condition guard
            return ApiResponseFactory::ok(['status' => 'already_processed']);
        }

        // Already processed by a previous request
        if (! $event->wasRecentlyCreated && $event->processed_at !== null) {
            return ApiResponseFactory::ok(['status' => 'already_processed']);
        }

        // Dispatch command — if the handler throws, processed_at stays null and Stripe will retry
        /** @var array<string, mixed> $object */
        $object = $payload['data']['object'] ?? [];
        $this->dispatchEvent($eventType, $object);

        $event->update(['processed_at' => now()]);

        return ApiResponseFactory::ok(['status' => 'ok']);
    }

    /**
     * @param array<string, mixed> $object
     */
    private function dispatchEvent(string $eventType, array $object): void
    {
        match ($eventType) {
            'customer.subscription.updated' => $this->commandBus->dispatch(
                new SyncSubscriptionStatusFromStripeCommand(
                    stripeSubscriptionId: $object['id'],
                    newStatus: $this->mapStripeStatus($object['status']),
                    currentPeriodEnd: isset($object['current_period_end']) ? (int) $object['current_period_end'] : null,
                )
            ),
            'customer.subscription.deleted' => $this->commandBus->dispatch(
                new ExpireSubscriptionFromStripeCommand(
                    stripeSubscriptionId: $object['id'],
                )
            ),
            'invoice.payment_succeeded' => $this->commandBus->dispatch(
                new SyncSubscriptionStatusFromStripeCommand(
                    stripeSubscriptionId: $object['subscription'],
                    newStatus: 'active',
                    currentPeriodEnd: isset($object['period_end']) ? (int) $object['period_end'] : null,
                )
            ),
            'invoice.payment_failed' => $this->commandBus->dispatch(
                new SyncSubscriptionStatusFromStripeCommand(
                    stripeSubscriptionId: $object['subscription'],
                    newStatus: 'past_due',
                    currentPeriodEnd: null,
                )
            ),
            'payment_intent.succeeded' => $this->commandBus->dispatch(
                new HandlePrepaidPaymentSucceededCommand(
                    paymentIntentId: $object['id'],
                    metadata: $object['metadata'] ?? [],
                )
            ),
            'payment_intent.payment_failed' => $this->commandBus->dispatch(
                new HandlePrepaidPaymentFailedCommand(
                    paymentIntentId: $object['id'],
                    metadata: $object['metadata'] ?? [],
                )
            ),
            default => null, // graceful ignore (e.g. customer.subscription.created)
        };
    }

    /**
     * Maps Stripe subscription statuses to our DB enum values.
     * Critical: Stripe returns 'canceled' (US), but DB enum uses 'cancelled' (British).
     */
    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active', 'trialing'                      => 'active',
            'past_due', 'incomplete', 'unpaid'        => 'past_due',
            'canceled'                                 => 'cancelled',
            'incomplete_expired'                       => 'expired',
            default                                    => $stripeStatus,
        };
    }
}
