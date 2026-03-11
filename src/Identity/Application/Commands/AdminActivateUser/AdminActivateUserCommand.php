<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AdminActivateUser;

use Src\Shared\Core\Bus\Command;

final class AdminActivateUserCommand implements Command
{
    public function __construct(
        public readonly int $userId,
        public readonly int $activatedBy,
    ) {}

    public function commandName(): string
    {
        return 'identity.admin_activate_user';
    }
}
