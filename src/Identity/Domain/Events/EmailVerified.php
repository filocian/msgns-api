<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class EmailVerified implements DomainEvent
{
    public function __construct(
        public int $userId,
        public string $email,
    ) {}

    public function eventName(): string
    {
        return 'identity.email_verified';
    }
}
