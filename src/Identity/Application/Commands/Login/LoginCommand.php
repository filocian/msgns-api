<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\Login;

use Src\Shared\Core\Bus\Command;

final class LoginCommand implements Command
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}

    public function commandName(): string
    {
        return 'identity.login';
    }
}
