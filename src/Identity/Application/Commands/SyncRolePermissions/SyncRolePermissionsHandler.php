<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\SyncRolePermissions;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Domain\Services\CoreRolePolicy;
use Src\Identity\Application\Resources\RoleResource;

final class SyncRolePermissionsHandler implements CommandHandler
{
    public function __construct(
        private readonly RolePort $roles,
        private readonly CoreRolePolicy $coreRolePolicy,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof SyncRolePermissionsCommand);

        // 1. Fetch role — throws NotFound (→ 404) if missing
        $role = $this->roles->findById($command->roleId);

        // 2. Guard — core roles cannot have permissions manually synced
        $this->coreRolePolicy->guardPermissionSync($role->name);

        // 3. Validate — every permission name must exist in the domain catalog
        $validNames = DomainPermissions::all();
        $invalid = array_diff($command->permissions, $validNames);
        if (!empty($invalid)) {
            throw ValidationFailed::because('invalid_permissions', [
                'invalid' => array_values($invalid),
                'message' => 'The following permissions do not exist in the catalog: ' . implode(', ', $invalid),
            ]);
        }

        // 4. Sync — full replace
        $this->roles->syncPermissionsByRoleId($command->roleId, $command->permissions);

        // 5. Return updated role state
        $updated = $this->roles->findById($command->roleId);

        return new RoleResource(
            id: $updated->id,
            name: $updated->name,
            permissions: $updated->permissions,
            usersCount: $updated->usersCount,
        );
    }
}
