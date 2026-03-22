<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AdminSetPassword;

use Src\Shared\Core\Bus\Command;

final class AdminSetPasswordCommand implements Command
{
    public function __construct(
        public readonly int $userId,
        public readonly string $hashedPassword,
    ) {}

    public function commandName(): string
    {
        return 'identity.admin_set_password';
    }
}
