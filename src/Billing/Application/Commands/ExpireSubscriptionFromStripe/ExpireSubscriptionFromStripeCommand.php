<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\ExpireSubscriptionFromStripe;

use Src\Shared\Core\Bus\Command;

final readonly class ExpireSubscriptionFromStripeCommand implements Command
{
    public function __construct(
        public string $stripeSubscriptionId,
    ) {}

    public function commandName(): string
    {
        return 'billing.expire_subscription';
    }
}
