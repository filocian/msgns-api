<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\CreateRole;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Application\Resources\RoleResource;

final class CreateRoleHandler implements CommandHandler
{
    public function __construct(
        private readonly RolePort $roles,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof CreateRoleCommand);

        $role = $this->roles->createRole($command->name);

        return new RoleResource(
            id: $role->id,
            name: $role->name,
            permissions: $role->permissions,
            usersCount: $role->usersCount,
        );
    }
}
