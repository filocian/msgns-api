<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\CreateSetupIntent;

use Src\Shared\Core\Bus\Command;

final readonly class CreateSetupIntentCommand implements Command
{
    public function __construct(
        public int $userId,
    ) {}

    public function commandName(): string
    {
        return 'billing.create_setup_intent';
    }
}
