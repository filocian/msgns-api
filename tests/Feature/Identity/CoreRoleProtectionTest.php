<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;

/**
 * Feature tests for core-role protection policy.
 *
 * These tests verify that the v2 Identity admin endpoints enforce
 * the CoreRolePolicy: developer, backoffice, and user roles cannot
 * be deleted or renamed.
 */

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    // Reconcile to ensure all 5 catalog roles exist in Spatie tables.
    $this->artisan('rbac:reconcile')->assertExitCode(0);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

describe('DELETE /api/v2/identity/admin/roles/{id}', function () {

    it('returns 403 when attempting to delete core role developer', function () {
        $role = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();

        $this->deleteJson("/api/v2/identity/admin/roles/{$role->id}")
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'core_role_protected');
    });

    it('returns 403 when attempting to delete core role backoffice', function () {
        $role = Role::where('name', 'backoffice')->where('guard_name', 'stateful-api')->first();

        $this->deleteJson("/api/v2/identity/admin/roles/{$role->id}")
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'core_role_protected');
    });

    it('returns 403 when attempting to delete core role user', function () {
        $role = Role::where('name', 'user')->where('guard_name', 'stateful-api')->first();

        $this->deleteJson("/api/v2/identity/admin/roles/{$role->id}")
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'core_role_protected');
    });

    it('returns 204 when deleting non-core role designer', function () {
        $role = Role::where('name', 'designer')->where('guard_name', 'stateful-api')->first();

        $this->deleteJson("/api/v2/identity/admin/roles/{$role->id}")
            ->assertStatus(204);

        expect(Role::find($role->id))->toBeNull();
    });

    it('returns 204 when deleting non-core role marketing', function () {
        $role = Role::where('name', 'marketing')->where('guard_name', 'stateful-api')->first();

        $this->deleteJson("/api/v2/identity/admin/roles/{$role->id}")
            ->assertStatus(204);

        expect(Role::find($role->id))->toBeNull();
    });

    it('returns 204 when deleting a custom role', function () {
        $customRole = $this->createRole('custom-test-role');

        $this->deleteJson("/api/v2/identity/admin/roles/{$customRole->id}")
            ->assertStatus(204);

        expect(Role::find($customRole->id))->toBeNull();
    });
});

describe('PATCH /api/v2/identity/admin/roles/{id}', function () {

    it('returns 403 when attempting to rename core role developer', function () {
        $role = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();

        $this->patchJson("/api/v2/identity/admin/roles/{$role->id}", ['name' => 'dev'])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'core_role_protected');
    });

    it('returns 403 when attempting to rename core role backoffice', function () {
        $role = Role::where('name', 'backoffice')->where('guard_name', 'stateful-api')->first();

        $this->patchJson("/api/v2/identity/admin/roles/{$role->id}", ['name' => 'back-admin'])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'core_role_protected');
    });

    it('returns 403 when attempting to rename core role user', function () {
        $role = Role::where('name', 'user')->where('guard_name', 'stateful-api')->first();

        $this->patchJson("/api/v2/identity/admin/roles/{$role->id}", ['name' => 'member'])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'core_role_protected');
    });

    it('allows renaming non-core role designer', function () {
        $role = Role::where('name', 'designer')->where('guard_name', 'stateful-api')->first();

        $this->patchJson("/api/v2/identity/admin/roles/{$role->id}", ['name' => 'graphic-designer'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'graphic-designer');
    });
});
