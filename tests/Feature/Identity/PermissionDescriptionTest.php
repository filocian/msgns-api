<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;
use Src\Identity\Domain\Permissions\DomainPermissions;

/**
 * Feature tests: GET /api/v2/identity/admin/permissions includes descriptions.
 *
 * REQ-07, REQ-08, REQ-09
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

describe('GET /api/v2/identity/admin/permissions — descriptions', function () {

    it('REQ-08-A: returns 200 for authenticated user with manage_roles_and_permissions', function () {
        $this->getJson('/api/v2/identity/admin/permissions')
            ->assertStatus(200);
    });

    it('REQ-08-A: each permission has a non-null description string', function () {
        $response = $this->getJson('/api/v2/identity/admin/permissions')
            ->assertStatus(200);

        $permissions = $response->json('data');
        expect($permissions)->not->toBeEmpty();

        foreach ($permissions as $permission) {
            expect($permission)->toHaveKey('description');
            expect($permission['description'])->toBeString();
            expect(strlen($permission['description']))->toBeGreaterThan(0);
        }
    });

    it('REQ-08-A: description for create_role matches DomainPermissions::descriptions()', function () {
        $response = $this->getJson('/api/v2/identity/admin/permissions')
            ->assertStatus(200);

        $permissions = $response->json('data');
        $descriptions = DomainPermissions::descriptions();

        $createRole = collect($permissions)->firstWhere('name', 'create_role');
        expect($createRole)->not->toBeNull();
        expect($createRole['description'])->toBe($descriptions['create_role']);
    });

    it('REQ-09-A: all 24 permissions have non-empty descriptions', function () {
        $response = $this->getJson('/api/v2/identity/admin/permissions')
            ->assertStatus(200);

        $permissions = $response->json('data');
        expect($permissions)->toHaveCount(24);

        foreach ($permissions as $permission) {
            expect(strlen($permission['description']))->toBeGreaterThan(0,
                "Permission '{$permission['name']}' has an empty description"
            );
        }
    });
});
