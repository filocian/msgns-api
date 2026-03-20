<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\ConfirmEmailChange;

use Src\Shared\Core\Bus\Command;

final class ConfirmEmailChangeCommand implements Command
{
    public function __construct(
        public readonly string $token,
    ) {}

    public function commandName(): string
    {
        return 'identity.confirm_email_change';
    }
}
