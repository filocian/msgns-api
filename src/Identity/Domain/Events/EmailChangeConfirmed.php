<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class EmailChangeConfirmed implements DomainEvent
{
    public function __construct(
        public int $userId,
        public string $oldEmail,
        public string $newEmail,
    ) {}

    public function eventName(): string
    {
        return 'identity.email_change_confirmed';
    }
}
