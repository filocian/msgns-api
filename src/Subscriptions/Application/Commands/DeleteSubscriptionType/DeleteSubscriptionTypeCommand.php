<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Commands\DeleteSubscriptionType;

use Src\Shared\Core\Bus\Command;

final readonly class DeleteSubscriptionTypeCommand implements Command
{
    public function __construct(
        public int $id,
    ) {}

    public function commandName(): string
    {
        return 'subscriptions.delete_subscription_type';
    }
}
