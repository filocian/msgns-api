<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\SyncRolePermissions;

use Src\Shared\Core\Bus\Command;

final class SyncRolePermissionsCommand implements Command
{
    /** @param string[] $permissions */
    public function __construct(
        public readonly int $roleId,
        public readonly array $permissions,
    ) {}

    public function commandName(): string
    {
        return 'identity.sync_role_permissions';
    }
}
