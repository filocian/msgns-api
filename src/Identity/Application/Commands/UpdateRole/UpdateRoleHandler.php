<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\UpdateRole;

use Src\Identity\Application\Resources\RoleResource;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Domain\Services\CoreRolePolicy;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

final class UpdateRoleHandler implements CommandHandler
{
    public function __construct(
        private readonly RolePort $roles,
        private readonly CoreRolePolicy $coreRolePolicy,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof UpdateRoleCommand);

        $this->coreRolePolicy->guardRename($command->id, $command->name, $this->roles);

        $role = $this->roles->updateRole($command->id, $command->name);

        return new RoleResource(
            id: $role->id,
            name: $role->name,
            permissions: $role->permissions,
            usersCount: $role->usersCount,
        );
    }
}
