<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AdminSetEmailVerified;

use Src\Shared\Core\Bus\Command;

final class AdminSetEmailVerifiedCommand implements Command
{
    public function __construct(
        public readonly int $userId,
    ) {}

    public function commandName(): string
    {
        return 'identity.admin_set_email_verified';
    }
}
