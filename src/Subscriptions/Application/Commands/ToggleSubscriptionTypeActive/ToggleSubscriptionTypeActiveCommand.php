<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Commands\ToggleSubscriptionTypeActive;

use Src\Shared\Core\Bus\Command;

final readonly class ToggleSubscriptionTypeActiveCommand implements Command
{
    public function __construct(
        public int $id,
    ) {}

    public function commandName(): string
    {
        return 'subscriptions.toggle_subscription_type_active';
    }
}
