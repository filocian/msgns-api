<?php

declare(strict_types=1);

use App\Models\User;


beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->artisan('rbac:reconcile')->assertExitCode(0);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

it('lists users', function () {
    $response = $this->getJson('/api/v2/identity/admin/users');
    $response->assertStatus(200)
             ->assertJsonStructure(['data', 'meta']);
});

it('shows a specific user', function () {
    $user = $this->create_user(['email' => 'user@example.com']);
    $response = $this->getJson("/api/v2/identity/admin/users/{$user->id}");
    $response->assertStatus(200)
             ->assertJsonPath('data.email', 'user@example.com');
});

it('returns 403 for non-admin users', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $regular = $this->create_user(['email' => 'regular@example.com']);
    $this->actingAs($regular, 'stateful-api');

    $response = $this->getJson('/api/v2/identity/admin/users');
    $response->assertStatus(403);
});

it('deactivates a user', function () {
    $user = $this->create_user(['email' => 'target@example.com']);
    $response = $this->patchJson("/api/v2/identity/admin/users/{$user->id}/deactivate");
    $response->assertStatus(200);
});

it('activates a deactivated user', function () {
    $user = $this->create_user(['email' => 'target@example.com', 'active' => false]);
    $response = $this->patchJson("/api/v2/identity/admin/users/{$user->id}/activate");
    $response->assertStatus(200);
});
