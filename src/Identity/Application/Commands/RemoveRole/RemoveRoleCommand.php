<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\RemoveRole;

use Src\Shared\Core\Bus\Command;

final class RemoveRoleCommand implements Command
{
    public function __construct(
        public readonly int $userId,
        public readonly string $role,
    ) {}

    public function commandName(): string
    {
        return 'identity.remove_role';
    }
}
