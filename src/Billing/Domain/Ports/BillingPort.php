<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Ports;

interface BillingPort
{
    public function createOrGetCustomer(int $userId): void;

    public function createSetupIntent(int $userId): string;

    /**
     * @return array<int, array{id: string, brand: string, last_four: string, exp_month: int, exp_year: int, is_default: bool}>
     */
    public function listPaymentMethods(int $userId): array;

    public function setDefaultPaymentMethod(int $userId, string $paymentMethodId): void;

    public function deletePaymentMethod(int $userId, string $paymentMethodId): void;

    public function hasActiveSubscriptions(int $userId): bool;

    public function paymentMethodBelongsToUser(int $userId, string $paymentMethodId): bool;
}
