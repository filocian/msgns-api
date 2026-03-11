<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AssignRole;

use Src\Shared\Core\Bus\Command;

final class AssignRoleCommand implements Command
{
    public function __construct(
        public readonly int $userId,
        public readonly string $role,
    ) {}

    public function commandName(): string
    {
        return 'identity.assign_role';
    }
}
