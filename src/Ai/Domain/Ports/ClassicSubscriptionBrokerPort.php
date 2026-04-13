<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Ports;

/**
 * Abstracts Stripe/Cashier classic subscription lifecycle operations.
 * Implementations live in Infrastructure\Adapters\.
 */
interface ClassicSubscriptionBrokerPort
{
    /**
     * Create a recurring Stripe subscription for the given user.
     * Returns the Stripe subscription ID and current period timestamps.
     *
     * @return array{stripe_subscription_id: string, current_period_start: int, current_period_end: int}
     * @throws \Src\Ai\Domain\Errors\SubscriptionRequires3DS when 3DS authentication is required
     */
    public function createSubscription(int $userId, string $stripePriceId, string $paymentMethodId): array;

    /**
     * Cancel the 'ai' Cashier subscription at period end (does not revoke access immediately).
     */
    public function cancelSubscription(int $userId): void;
}
