<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Authorization;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Src\Identity\Domain\DTOs\PermissionData;
use Src\Identity\Domain\DTOs\RoleData;
use Src\Identity\Domain\Ports\RolePort;
use Src\Shared\Core\Errors\NotFound;

final class SpatieRoleAdapter implements RolePort
{
    private const GUARD = 'stateful-api';

    public function getRolesForUser(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }
        return $user->getRoleNames()->toArray();
    }

    public function getPermissionsForUser(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }
        return $user->getAllPermissions()->pluck('name')->toArray();
    }

    public function hasRole(int $userId, string $role): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        return $user->hasRole($role, self::GUARD);
    }

    public function assignRole(int $userId, string $role): void
    {
        $user = User::findOrFail($userId);
        $roleModel = Role::findOrCreate($role, self::GUARD);
        $user->assignRole($roleModel);
    }

    public function removeRole(int $userId, string $role): void
    {
        $user = User::findOrFail($userId);
        $user->removeRole($role);
    }

    public function listRoles(): array
    {
        return Role::where('guard_name', self::GUARD)
            ->withCount('users')
            ->get()
            ->map(fn(Role $r) => new RoleData(
                id: (int) $r->id,
                name: $r->name,
                permissions: $r->permissions->pluck('name')->toArray(),
                usersCount: (int) ($r->users_count ?? 0),
            ))
            ->toArray();
    }

    public function listPermissions(): array
    {
        return Permission::where('guard_name', self::GUARD)
            ->get()
            ->map(fn(Permission $p) => new PermissionData(
                id: (int) $p->id,
                name: $p->name,
            ))
            ->toArray();
    }

    public function findById(int $id): RoleData
    {
        $role = Role::withCount('users')->find($id);
        if (!$role) {
            throw NotFound::because('role_not_found');
        }
        return new RoleData(
            id: (int) $role->id,
            name: $role->name,
            permissions: $role->permissions->pluck('name')->toArray(),
            usersCount: (int) ($role->users_count ?? 0),
        );
    }

    public function createRole(string $name, string $guard = self::GUARD): RoleData
    {
        $role = Role::findOrCreate($name, $guard);
        return new RoleData(id: (int) $role->id, name: $role->name, permissions: [], usersCount: 0);
    }

    public function updateRole(int $id, string $name): RoleData
    {
        $role = Role::find($id);
        if (!$role) {
            throw NotFound::because('role_not_found');
        }
        $role->name = $name;
        $role->save();
        return new RoleData(
            id: (int) $role->id,
            name: $role->name,
            permissions: $role->permissions->pluck('name')->toArray(),
            usersCount: 0,
        );
    }

    public function deleteRole(int $id): void
    {
        $role = Role::find($id);
        if (!$role) {
            throw NotFound::because('role_not_found');
        }
        $role->delete();
    }

    public function syncPermissionsByRoleId(int $roleId, array $permissionNames): void
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw NotFound::because('role_not_found');
        }

        $permissionModels = collect($permissionNames)->map(
            fn(string $name) => Permission::findOrCreate($name, self::GUARD)
        );

        $role->syncPermissions($permissionModels);
    }

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
    public function syncRolePermissions(string $role, array $permissions): void
    {
        $roleModel = Role::findOrCreate($role, self::GUARD);
        $permissionModels = collect($permissions)->map(
            fn(string $permName) => Permission::findOrCreate($permName, self::GUARD)
        );
        $roleModel->syncPermissions($permissionModels);
    }

    public function syncRoles(int $userId, array $roles): void
    {
        $user = User::findOrFail($userId);
        $user->syncRoles($roles);
    }

    public function inTransaction(callable $fn): void
    {
        DB::transaction(static function () use ($fn): void {
            $fn();
        });
    }
}
