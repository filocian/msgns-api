<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Commands\CreateSubscriptionType;

use Src\Shared\Core\Bus\Command;

final readonly class CreateSubscriptionTypeCommand implements Command
{
    public function __construct(
        public string $name,
        public ?string $description,
        public string $permissionName,
        public int $googleReviewLimit,
        public int $instagramContentLimit,
        public string $stripeProductId,
    ) {}

    public function commandName(): string
    {
        return 'subscriptions.create_subscription_type';
    }
}
