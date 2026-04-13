<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Adapters;

use App\Models\User;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Src\Ai\Domain\Errors\SubscriptionRequires3DS;
use Src\Ai\Domain\Ports\ClassicSubscriptionBrokerPort;

final class CashierSubscriptionAdapter implements ClassicSubscriptionBrokerPort
{
    public function createSubscription(int $userId, string $stripePriceId, string $paymentMethodId): array
    {
        $user = User::findOrFail($userId);

        try {
            $cashierSub = $user
                ->newSubscription('ai', $stripePriceId)
                ->create($paymentMethodId);
        } catch (IncompletePayment $e) {
            throw SubscriptionRequires3DS::forPaymentIntent($e->payment->clientSecret());
        }

        $stripeSub = $cashierSub->asStripeSubscription();

        /** @var int $periodStart */
        $periodStart = $stripeSub['current_period_start'];
        /** @var int $periodEnd */
        $periodEnd = $stripeSub['current_period_end'];

        return [
            'stripe_subscription_id' => $stripeSub->id,
            'current_period_start'   => $periodStart,
            'current_period_end'     => $periodEnd,
        ];
    }

    public function cancelSubscription(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->subscription('ai')?->cancel();
    }
}
