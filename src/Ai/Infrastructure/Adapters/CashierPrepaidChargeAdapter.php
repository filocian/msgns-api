<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Adapters;

use App\Models\User;
use Laravel\Cashier\Payment;
use Src\Ai\Domain\Ports\PrepaidChargePort;

final class CashierPrepaidChargeAdapter implements PrepaidChargePort
{
    public function charge(int $userId, int $amountCents, string $paymentMethodId): Payment
    {
        /** @var User $user */
        $user = User::findOrFail($userId);

        return $user->charge($amountCents, $paymentMethodId);
    }
}
