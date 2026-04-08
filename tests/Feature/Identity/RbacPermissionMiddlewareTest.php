<?php

declare(strict_types=1);

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Feature tests: Permission-based middleware for admin RBAC routes.
 *
 * REQ-10, REQ-11, REQ-12, REQ-13, REQ-14
 */

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->artisan('rbac:reconcile')->assertExitCode(0);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

describe('Admin route access via permission:manage_roles_and_permissions', function () {

    it('REQ-11-A: developer role user can access GET /admin/roles (has all perms)', function () {
        $user = $this->create_user(['email' => 'dev@example.com']);
        $role = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();
        $user->assignRole($role);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/identity/admin/roles')
            ->assertStatus(200);
    });

    it('REQ-12-A: backoffice role user can access GET /admin/roles (has all perms)', function () {
        $user = $this->create_user(['email' => 'backoffice@example.com']);
        $role = Role::where('name', 'backoffice')->where('guard_name', 'stateful-api')->first();
        $user->assignRole($role);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/identity/admin/roles')
            ->assertStatus(200);
    });

    it('REQ-13-A: custom role with manage_roles_and_permissions can access GET /admin/roles', function () {
        $user = $this->create_user(['email' => 'rbac-admin@example.com']);
        $customRole = $this->createRole('rbac-admin');
        $permission = Permission::where('name', 'manage_roles_and_permissions')
            ->where('guard_name', 'stateful-api')
            ->first();
        $customRole->givePermissionTo($permission);
        $user->assignRole($customRole);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/identity/admin/roles')
            ->assertStatus(200);
    });

    it('REQ-14-A: user role (no manage_roles_and_permissions) returns 403', function () {
        $user = $this->create_user(['email' => 'user@example.com']);
        $role = Role::where('name', 'user')->where('guard_name', 'stateful-api')->first();
        $user->assignRole($role);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/identity/admin/roles')
            ->assertStatus(403);
    });

    it('REQ-14-B: designer role (no manage_roles_and_permissions) returns 403', function () {
        $user = $this->create_user(['email' => 'designer@example.com']);
        $role = Role::where('name', 'designer')->where('guard_name', 'stateful-api')->first();
        $user->assignRole($role);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/identity/admin/roles')
            ->assertStatus(403);
    });

    it('REQ-14-C: authenticated user with no roles returns 403', function () {
        $user = $this->create_user(['email' => 'norole@example.com']);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/identity/admin/roles')
            ->assertStatus(403);
    });
});
