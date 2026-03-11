<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\RequestVerification;

use Src\Shared\Core\Bus\Command;

final class RequestVerificationCommand implements Command
{
    public function __construct(
        public readonly string $email,
    ) {}

    public function commandName(): string
    {
        return 'identity.request_verification';
    }
}
