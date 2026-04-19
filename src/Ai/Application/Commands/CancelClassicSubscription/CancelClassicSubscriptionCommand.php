<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\CancelClassicSubscription;

use Src\Shared\Core\Bus\Command;

final readonly class CancelClassicSubscriptionCommand implements Command
{
    public function __construct(
        public int $userId,
    ) {}

    public function commandName(): string
    {
        return 'ai.cancel_classic_subscription';
    }
}
