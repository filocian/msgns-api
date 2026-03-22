<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AdminDeactivateUser;

use Src\Shared\Core\Bus\Command;

final class AdminDeactivateUserCommand implements Command
{
    public function __construct(
        public readonly int $userId,
        public readonly int $deactivatedBy,
    ) {}

    public function commandName(): string
    {
        return 'identity.admin_deactivate_user';
    }
}
