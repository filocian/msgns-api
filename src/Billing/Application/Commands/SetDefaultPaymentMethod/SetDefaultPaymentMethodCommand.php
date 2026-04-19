<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\SetDefaultPaymentMethod;

use Src\Shared\Core\Bus\Command;

final readonly class SetDefaultPaymentMethodCommand implements Command
{
    public function __construct(
        public int $userId,
        public string $paymentMethodId,
    ) {}

    public function commandName(): string
    {
        return 'billing.set_default_payment_method';
    }
}
