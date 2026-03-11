<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\VerifyEmail;

use Src\Shared\Core\Bus\Command;

final class VerifyEmailCommand implements Command
{
    public function __construct(
        public readonly string $token,
    ) {}

    public function commandName(): string
    {
        return 'identity.verify_email';
    }
}
