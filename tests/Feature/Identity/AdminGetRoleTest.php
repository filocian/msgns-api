<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;

/**
 * Feature tests: GET /api/v2/identity/admin/roles/{id}
 *
 * REQ-15, REQ-16, REQ-17, REQ-18
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

describe('GET /api/v2/identity/admin/roles/{id}', function () {

    it('REQ-15-A: returns 200 with correct JSON shape for existing role', function () {
        $role = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();

        $this->getJson("/api/v2/identity/admin/roles/{$role->id}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'permissions', 'usersCount'],
            ])
            ->assertJsonPath('data.id', $role->id)
            ->assertJsonPath('data.name', 'developer');
    });

    it('REQ-15-B: role with permissions returns permissions array', function () {
        $role = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();

        $response = $this->getJson("/api/v2/identity/admin/roles/{$role->id}")
            ->assertStatus(200);

        $permissions = $response->json('data.permissions');
        expect($permissions)->toContain('create_role');
        expect($permissions)->toContain('manage_roles_and_permissions');
    });

    it('REQ-15-C: role with users shows correct usersCount', function () {
        $role = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();
        // Admin user already has this role assigned in beforeEach

        $response = $this->getJson("/api/v2/identity/admin/roles/{$role->id}")
            ->assertStatus(200);

        $usersCount = $response->json('data.usersCount');
        expect($usersCount)->toBe(1);
    });

    it('REQ-16-A: returns 404 for non-existent role', function () {
        $this->getJson('/api/v2/identity/admin/roles/99999')
            ->assertStatus(404);
    });

    it('REQ-17-A: returns 401 for unauthenticated request', function () {
        auth()->guard('stateful-api')->logout();

        $role = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();

        $this->getJson("/api/v2/identity/admin/roles/{$role->id}")
            ->assertStatus(401);
    });

    it('REQ-18-A: returns 403 for user without manage_roles_and_permissions', function () {
        $regularUser = $this->create_user(['email' => 'regular@example.com']);
        $userRole = Role::where('name', 'user')->where('guard_name', 'stateful-api')->first();
        $regularUser->assignRole($userRole);

        $this->actingAs($regularUser, 'stateful-api');

        $role = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();

        $this->getJson("/api/v2/identity/admin/roles/{$role->id}")
            ->assertStatus(403);
    });

    it('custom role with 0 users returns usersCount of 0', function () {
        $customRole = $this->createRole('custom-test-role');

        $this->getJson("/api/v2/identity/admin/roles/{$customRole->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'custom-test-role')
            ->assertJsonPath('data.usersCount', 0)
            ->assertJsonPath('data.permissions', []);
    });
});
