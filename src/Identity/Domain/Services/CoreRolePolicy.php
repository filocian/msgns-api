<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Services;

use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Domain\ValueObjects\RbacCatalog;
use Src\Shared\Core\Errors\Unauthorized;

/**
 * Enforces invariants that protect core roles from deletion or renaming.
 *
 * Core roles (developer, backoffice, user) are foundational to the
 * application's authorization model. Removing or renaming them would
 * break legacy FormRequests and all downstream role checks.
 *
 * This is a pure domain service — no framework imports, no Spatie references.
 */
final class CoreRolePolicy
{
    /**
     * Guard against deleting a core role.
     *
     * @throws Unauthorized with code 'core_role_protected'
     */
    public function guardDeletion(string $roleName): void
    {
        if (in_array($roleName, RbacCatalog::coreRoleNames(), strict: true)) {
            throw Unauthorized::because('core_role_protected', [
                'role' => $roleName,
                'reason' => 'Core roles cannot be deleted',
            ]);
        }
    }

    /**
     * Guard against syncing permissions on a core role.
     *
     * Core role permissions are managed exclusively by the RBAC catalog
     * reconciliation process. Manual sync is not allowed.
     *
     * @throws Unauthorized with code 'core_role_protected'
     */
    public function guardPermissionSync(string $roleName): void
    {
        if (in_array($roleName, RbacCatalog::coreRoleNames(), strict: true)) {
            throw Unauthorized::because('core_role_protected', [
                'role' => $roleName,
                'reason' => 'Core role permissions are managed by the RBAC catalog and cannot be manually synced',
            ]);
        }
    }

    /**
     * Guard against renaming a core role.
     *
     * Resolves the current name from the RolePort by ID, then checks the
     * catalog. If the current name is a core role name, renaming is blocked.
     *
     * @throws Unauthorized with code 'core_role_protected'
     */
    public function guardRename(int $roleId, string $newName, RolePort $roles): void
    {
        $roleData = $roles->findById($roleId);

        if (in_array($roleData->name, RbacCatalog::coreRoleNames(), strict: true)) {
            throw Unauthorized::because('core_role_protected', [
                'role' => $roleData->name,
                'reason' => 'Core roles cannot be renamed',
            ]);
        }
    }
}
