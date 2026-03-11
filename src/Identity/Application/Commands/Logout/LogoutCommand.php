<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\Logout;

use Src\Shared\Core\Bus\Command;

final class LogoutCommand implements Command
{
    public function __construct(
        public readonly int $userId,
    ) {}

    public function commandName(): string
    {
        return 'identity.logout';
    }
}
