<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class EmailChangeRequested implements DomainEvent
{
    public function __construct(
        public int $userId,
        public string $currentEmail,
        public string $newEmail,
        public string $token,
    ) {}

    public function eventName(): string
    {
        return 'identity.email_change_requested';
    }
}
