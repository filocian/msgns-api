<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\RequestEmailChange;

use Src\Shared\Core\Bus\Command;

final class RequestEmailChangeCommand implements Command
{
    public function __construct(
        public readonly int $userId,
        public readonly string $newEmail,
        public readonly string $password,
    ) {}

    public function commandName(): string
    {
        return 'identity.request_email_change';
    }
}
