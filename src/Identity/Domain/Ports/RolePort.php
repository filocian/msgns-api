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
    /** @param string[] $permissions */
    public function syncRolePermissions(string $role, array $permissions): void;

    /**
     * Execute the given callable inside a database transaction.
     *
     * If the callable throws an exception, all writes performed within it
     * are rolled back and the exception is re-thrown to the caller.
     */
    public function inTransaction(callable $fn): void;
}
