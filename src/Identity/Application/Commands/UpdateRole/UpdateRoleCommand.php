<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\UpdateRole;

use Src\Shared\Core\Bus\Command;

final class UpdateRoleCommand implements Command
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}

    public function commandName(): string
    {
        return 'identity.update_role';
    }
}
