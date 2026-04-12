<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Commands\UpdateSubscriptionType;

use Src\Shared\Core\Bus\Command;

final readonly class UpdateSubscriptionTypeCommand implements Command
{
    /**
     * @param list<string>|null $billingPeriods
     */
    public function __construct(
        public int $id,
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
        return 'subscriptions.update_subscription_type';
    }
}
