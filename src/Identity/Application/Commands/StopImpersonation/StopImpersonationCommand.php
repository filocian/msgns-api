<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\StopImpersonation;

use Src\Shared\Core\Bus\Command;

final class StopImpersonationCommand implements Command
{
    public function __construct(
        public readonly int $adminUserId,
    ) {}

    public function commandName(): string
    {
        return 'identity.stop_impersonation';
    }
}
