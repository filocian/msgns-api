<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\GoogleLogin;

use Src\Shared\Core\Bus\Command;

final class GoogleLoginCommand implements Command
{
    public function __construct(
        public readonly string $idToken,
    ) {}

    public function commandName(): string
    {
        return 'identity.google_login';
    }
}
