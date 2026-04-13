<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Services;

use App\Models\User;
use Src\Billing\Domain\Ports\BillingPort;

final class StripeCustomerService implements BillingPort
{
    public function createOrGetCustomer(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->createOrGetStripeCustomer();
    }

    public function createSetupIntent(int $userId): string
    {
        $user = User::findOrFail($userId);
        $intent = $user->createSetupIntent();

        return $intent->client_secret;
    }

    /**
     * @return array<int, array{id: string, brand: string, last_four: string, exp_month: int, exp_year: int, is_default: bool}>
     */
    public function listPaymentMethods(int $userId): array
    {
        $user = User::findOrFail($userId);
        $defaultId = $user->defaultPaymentMethod()?->id;
        $methods = $user->paymentMethods();

        return $methods->map(static function ($pm) use ($defaultId): array {
            return [
                'id' => $pm->id,
                'brand' => $pm->card->brand,
                'last_four' => $pm->card->last4,
                'exp_month' => $pm->card->exp_month,
                'exp_year' => $pm->card->exp_year,
                'is_default' => $pm->id === $defaultId,
            ];
        })->values()->all();
    }

    public function setDefaultPaymentMethod(int $userId, string $paymentMethodId): void
    {
        $user = User::findOrFail($userId);
        $user->updateDefaultPaymentMethod($paymentMethodId);
    }

    public function deletePaymentMethod(int $userId, string $paymentMethodId): void
    {
        $user = User::findOrFail($userId);
        $pm = $user->findPaymentMethod($paymentMethodId);

        if ($pm !== null) {
            $pm->delete();
        }
    }

    public function hasActiveSubscriptions(int $userId): bool
    {
        $user = User::findOrFail($userId);

        return $user->subscriptions()->where('stripe_status', 'active')->exists();
    }

    public function paymentMethodBelongsToUser(int $userId, string $paymentMethodId): bool
    {
        $user = User::findOrFail($userId);
        $pm = $user->findPaymentMethod($paymentMethodId);

        return $pm !== null;
    }
}
