<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class VerificationRequested implements DomainEvent
{
    public function __construct(
        public string $email,
        public string $token,
    ) {}

    public function eventName(): string
    {
        return 'identity.verification_requested';
    }
}
