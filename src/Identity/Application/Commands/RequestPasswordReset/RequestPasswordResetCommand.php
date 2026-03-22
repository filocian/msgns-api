<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\RequestPasswordReset;

use Src\Shared\Core\Bus\Command;

final class RequestPasswordResetCommand implements Command
{
    public function __construct(
        public readonly string $email,
    ) {}

    public function commandName(): string
    {
        return 'identity.request_password_reset';
    }
}
