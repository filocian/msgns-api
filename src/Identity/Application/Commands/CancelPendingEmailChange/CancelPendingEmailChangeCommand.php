<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\CancelPendingEmailChange;

use Src\Shared\Core\Bus\Command;

final class CancelPendingEmailChangeCommand implements Command
{
    public function __construct(
        public readonly int $userId,
    ) {}

    public function commandName(): string
    {
        return 'identity.cancel_pending_email_change';
    }
}
