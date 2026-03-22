<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class PasswordChanged implements DomainEvent
{
    public function __construct(
        public int $userId,
        public bool $selfService,
    ) {}

    public function eventName(): string
    {
        return 'identity.password_changed';
    }
}
