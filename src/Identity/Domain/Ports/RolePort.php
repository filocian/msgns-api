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
    public function createRole(string $name, string $guard = 'stateful-api'): RoleData;
    public function updateRole(int $id, string $name): RoleData;
    public function deleteRole(int $id): void;
}
