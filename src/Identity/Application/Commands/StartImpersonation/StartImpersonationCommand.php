<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\StartImpersonation;

use Src\Shared\Core\Bus\Command;

final class StartImpersonationCommand implements Command
{
    public function __construct(
        public readonly int $adminUserId,
        public readonly int $targetUserId,
    ) {}

    public function commandName(): string
    {
        return 'identity.start_impersonation';
    }
}
