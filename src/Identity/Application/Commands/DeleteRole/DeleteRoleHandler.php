<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\DeleteRole;

use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Domain\Services\CoreRolePolicy;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

final class DeleteRoleHandler implements CommandHandler
{
    public function __construct(
        private readonly RolePort $roles,
        private readonly CoreRolePolicy $coreRolePolicy,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof DeleteRoleCommand);

        $role = $this->roles->findById($command->id);
        $this->coreRolePolicy->guardDeletion($role->name);

        $this->roles->deleteRole($command->id);

        return null;
    }
}
