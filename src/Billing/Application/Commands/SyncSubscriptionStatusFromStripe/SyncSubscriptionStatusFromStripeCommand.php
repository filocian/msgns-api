<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\SyncSubscriptionStatusFromStripe;

use Src\Shared\Core\Bus\Command;

final readonly class SyncSubscriptionStatusFromStripeCommand implements Command
{
    public function __construct(
        public string $stripeSubscriptionId,
        public string $newStatus,
        public ?int $currentPeriodEnd = null,
    ) {}

    public function commandName(): string
    {
        return 'billing.sync_subscription_status';
    }
}
