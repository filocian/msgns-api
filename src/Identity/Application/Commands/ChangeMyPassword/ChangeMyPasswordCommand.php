<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\ChangeMyPassword;

use Src\Shared\Core\Bus\Command;

final class ChangeMyPasswordCommand implements Command
{
    public function __construct(
        public readonly int $userId,
        public readonly string $currentPassword,
        public readonly string $newHashedPassword,
    ) {}

    public function commandName(): string
    {
        return 'identity.change_my_password';
    }
}
