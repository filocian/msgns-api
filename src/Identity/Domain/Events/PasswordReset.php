<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class PasswordReset implements DomainEvent
{
    public function __construct(
        public int $userId,
    ) {}

    public function eventName(): string
    {
        return 'identity.password_reset';
    }
}
