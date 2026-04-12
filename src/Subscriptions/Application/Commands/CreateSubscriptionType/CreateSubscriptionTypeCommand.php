<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Commands\CreateSubscriptionType;

use Src\Shared\Core\Bus\Command;

final readonly class CreateSubscriptionTypeCommand implements Command
{
    /**
     * @param list<string>|null $billingPeriods
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public string $mode,
        public ?array $billingPeriods,
        public int $basePriceCents,
        public string $permissionName,
        public int $googleReviewLimit,
        public int $instagramContentLimit,
    ) {}

    public function commandName(): string
    {
        return 'subscriptions.create_subscription_type';
    }
}
