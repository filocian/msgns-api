<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\DeleteRole;

use Src\Shared\Core\Bus\Command;

final class DeleteRoleCommand implements Command
{
    public function __construct(
        public readonly int $id,
    ) {}

    public function commandName(): string
    {
        return 'identity.delete_role';
    }
}
