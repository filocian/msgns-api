<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\DeletePaymentMethod;

use Src\Shared\Core\Bus\Command;

final readonly class DeletePaymentMethodCommand implements Command
{
    public function __construct(
        public int $userId,
        public string $paymentMethodId,
    ) {}

    public function commandName(): string
    {
        return 'billing.delete_payment_method';
    }
}
