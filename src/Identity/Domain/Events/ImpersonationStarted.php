<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class ImpersonationStarted implements DomainEvent
{
    public function __construct(
        public int $adminUserId,
        public int $targetUserId,
    ) {}

    public function eventName(): string
    {
        return 'identity.impersonation_started';
    }
}
