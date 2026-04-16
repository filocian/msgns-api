<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\HandlePrepaidPaymentFailed;

use Src\Shared\Core\Bus\Command;

final readonly class HandlePrepaidPaymentFailedCommand implements Command
{
    /**
     * @param array<string, string> $metadata Stripe PaymentIntent metadata
     */
    public function __construct(
        public string $paymentIntentId,
        public array $metadata,
    ) {}

    public function commandName(): string
    {
        return 'billing.handle_prepaid_payment_failed';
    }
}
