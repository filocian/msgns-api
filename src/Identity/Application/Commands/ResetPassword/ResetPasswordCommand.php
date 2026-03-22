<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\ResetPassword;

use Src\Shared\Core\Bus\Command;

final class ResetPasswordCommand implements Command
{
    public function __construct(
        public readonly string $token,
        public readonly string $newHashedPassword,
    ) {}

    public function commandName(): string
    {
        return 'identity.reset_password';
    }
}
