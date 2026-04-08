<?php

declare(strict_types=1);

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Feature tests: PUT /api/v2/identity/admin/roles/{id}/permissions
 *
 * REQ-20, REQ-21, REQ-22, REQ-23, REQ-24, REQ-25, REQ-26, REQ-27
 */

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->artisan('rbac:reconcile')->assertExitCode(0);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

describe('PUT /api/v2/identity/admin/roles/{id}/permissions', function () {

    it('REQ-20-A / REQ-21-A: syncs valid permissions on custom role and returns 200', function () {
        $customRole = $this->createRole('custom-sync-role');

        $response = $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [
            'permissions' => ['create_role', 'edit_role'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'permissions', 'usersCount']]);

        $permissions = $response->json('data.permissions');
        expect($permissions)->toContain('create_role');
        expect($permissions)->toContain('edit_role');
        expect($permissions)->toHaveCount(2);
    });

    it('REQ-21-A: full replace — previous permissions not in new set are removed', function () {
        $customRole = $this->createRole('custom-replace-role');

        // First sync — set 3 permissions
        $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [
            'permissions' => ['create_role', 'edit_role', 'assign_role'],
        ])->assertStatus(200);

        // Second sync — replace with 1 permission
        $response = $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [
            'permissions' => ['create_role'],
        ])->assertStatus(200);

        $permissions = $response->json('data.permissions');
        expect($permissions)->toContain('create_role');
        expect($permissions)->not->toContain('edit_role');
        expect($permissions)->not->toContain('assign_role');
        expect($permissions)->toHaveCount(1);
    });

    it('REQ-22-A: unknown permission name returns 422 (domain validation → 400 in this project)', function () {
        $customRole = $this->createRole('custom-invalid-role');

        // Domain-level validation (unknown permission) maps to ValidationFailed → 422 HTTP status via DomainExceptionHandler
        $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [
            'permissions' => ['create_role', 'nonexistent_permission'],
        ])->assertStatus(422);
    });

    it('REQ-22-B: all known permission names returns 200', function () {
        $customRole = $this->createRole('custom-known-role');

        $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [
            'permissions' => ['create_role'],
        ])->assertStatus(200);
    });

    it('REQ-22-C: missing permissions key returns 400 (FormRequest validation)', function () {
        $customRole = $this->createRole('custom-missing-key-role');

        // FormRequest format validation → Laravel ValidationException → mapped to 400 in this project
        $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [])
            ->assertStatus(400);
    });

    it('REQ-22-D: permissions is a string not an array returns 400 (FormRequest validation)', function () {
        $customRole = $this->createRole('custom-string-perms-role');

        // FormRequest format validation → Laravel ValidationException → mapped to 400 in this project
        $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [
            'permissions' => 'create_role',
        ])->assertStatus(400);
    });

    it('REQ-23-A: syncing permissions on developer role returns 403 with core_role_protected', function () {
        $role = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();

        $this->putJson("/api/v2/identity/admin/roles/{$role->id}/permissions", [
            'permissions' => ['create_role'],
        ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'core_role_protected');
    });

    it('REQ-23-B: syncing permissions on backoffice role returns 403', function () {
        $role = Role::where('name', 'backoffice')->where('guard_name', 'stateful-api')->first();

        $this->putJson("/api/v2/identity/admin/roles/{$role->id}/permissions", [
            'permissions' => ['create_role'],
        ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'core_role_protected');
    });

    it('REQ-23-C: syncing permissions on user role returns 403', function () {
        $role = Role::where('name', 'user')->where('guard_name', 'stateful-api')->first();

        $this->putJson("/api/v2/identity/admin/roles/{$role->id}/permissions", [
            'permissions' => [],
        ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'core_role_protected');
    });

    it('REQ-23-D: syncing permissions on designer role (non-core) returns 200', function () {
        $role = Role::where('name', 'designer')->where('guard_name', 'stateful-api')->first();

        $this->putJson("/api/v2/identity/admin/roles/{$role->id}/permissions", [
            'permissions' => ['export_data'],
        ])->assertStatus(200);
    });

    it('REQ-24-A: non-existent role returns 404', function () {
        $this->putJson('/api/v2/identity/admin/roles/99999/permissions', [
            'permissions' => [],
        ])->assertStatus(404);
    });

    it('REQ-25-A: empty permissions array removes all permissions and returns 200', function () {
        $customRole = $this->createRole('custom-empty-perms-role');

        // First give it a permission
        $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [
            'permissions' => ['create_role', 'edit_role'],
        ])->assertStatus(200);

        // Now clear all permissions
        $response = $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [
            'permissions' => [],
        ])->assertStatus(200);

        expect($response->json('data.permissions'))->toBe([]);
    });

    it('REQ-26-A: unauthenticated request returns 401', function () {
        auth()->guard('stateful-api')->logout();
        $customRole = $this->createRole('custom-auth-test-role');

        $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [
            'permissions' => [],
        ])->assertStatus(401);
    });

    it('REQ-27-A: user without manage_roles_and_permissions returns 403', function () {
        $regularUser = $this->create_user(['email' => 'regular@example.com']);
        $userRole = Role::where('name', 'user')->where('guard_name', 'stateful-api')->first();
        $regularUser->assignRole($userRole);
        $this->actingAs($regularUser, 'stateful-api');

        $customRole = $this->createRole('custom-no-perm-role');

        $this->putJson("/api/v2/identity/admin/roles/{$customRole->id}/permissions", [
            'permissions' => ['create_role'],
        ])->assertStatus(403);
    });
});
