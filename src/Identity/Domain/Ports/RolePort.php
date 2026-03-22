<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Ports;

use Src\Identity\Domain\DTOs\RoleData;
use Src\Identity\Domain\DTOs\PermissionData;

interface RolePort
{
    /** @return string[] */
    public function getRolesForUser(int $userId): array;
    /** @return string[] */
    public function getPermissionsForUser(int $userId): array;
    public function hasRole(int $userId, string $role): bool;
    public function assignRole(int $userId, string $role): void;
    public function removeRole(int $userId, string $role): void;
    /** @return RoleData[] */
    public function listRoles(): array;
    /** @return PermissionData[] */
    public function listPermissions(): array;
    public function findById(int $id): RoleData;
    public function createRole(string $name, string $guard = 'stateful-api'): RoleData;
    public function updateRole(int $id, string $name): RoleData;
    public function deleteRole(int $id): void;

    /**
     * Sync the given role's permissions to exactly match the provided list.
     *
     * This operation REPLACES the full permission set for the role — it is NOT
     * additive. Any permissions previously assigned to this role that are absent
     * from `$permissions` will be REMOVED.
     *
     * This means manually-added permissions (not present in the RBAC catalog)
     * WILL be stripped on reconcile. This is by-design: the catalog is the
     * single source of truth for catalog-defined roles.
     *
     * The operation is idempotent: running it N times with the same input
     * produces the same final state.
     *
     * @param string[] $permissions
     */
    public function syncRolePermissions(string $role, array $permissions): void;

    /**
     * Replace the user's roles with exactly the provided set.
     *
     * This operation REPLACES the full role set for the user — it is NOT
     * additive. Any roles previously assigned to this user that are absent
     * from `$roles` will be REMOVED.
     *
     * @param string[] $roles
     */
    public function syncRoles(int $userId, array $roles): void;

    /**
     * Execute the given callable inside a database transaction.
     *
     * If the callable throws an exception, all writes performed within it
     * are rolled back and the exception is re-thrown to the caller.
     */
    public function inTransaction(callable $fn): void;
}
