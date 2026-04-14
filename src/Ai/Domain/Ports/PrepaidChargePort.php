<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Ports;

use Laravel\Cashier\Payment;

/**
 * Abstracts Stripe/Cashier one-time charge operations for prepaid packages.
 * Implementations live in Infrastructure\Adapters\.
 */
interface PrepaidChargePort
{
    /**
     * Charge a user once for a prepaid package.
     *
     * @throws \Laravel\Cashier\Exceptions\IncompletePayment when 3DS action is required
     * @throws \Stripe\Exception\CardException when the card is declined
     * @throws \Stripe\Exception\ApiErrorException on any other Stripe API error
     */
    public function charge(int $userId, int $amountCents, string $paymentMethodId): Payment;
}
