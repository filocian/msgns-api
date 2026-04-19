<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->artisan('rbac:reconcile')->assertExitCode(0);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'backoffice')->where('guard_name', 'stateful-api')->first();
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

it('returns 204 with valid password (backoffice role)', function () {
    $user = $this->create_user(['email' => 'target@example.com']);
    $response = $this->putJson("/api/v2/identity/admin/users/{$user->id}/password", [
        'password' => 'AdminSet123!',
    ]);
    $response->assertStatus(204);

    $user->refresh();
    expect(Hash::check('AdminSet123!', $user->password))->toBeTrue();
});

it('returns 404 for non-existent user', function () {
    $response = $this->putJson('/api/v2/identity/admin/users/99999/password', [
        'password' => 'AdminSet123!',
    ]);
    $response->assertStatus(404);
});

it('returns 400 validation error with password too short', function () {
    $user = $this->create_user(['email' => 'target@example.com']);
    $response = $this->putJson("/api/v2/identity/admin/users/{$user->id}/password", [
        'password' => 'short',
    ]);
    $response->assertStatus(400)
             ->assertJsonPath('error.code', 'validation_failed');
});

it('returns 403 for non-admin user', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $regular = $this->create_user(['email' => 'regular@example.com']);
    $this->actingAs($regular, 'stateful-api');

    $target = $this->create_user(['email' => 'target2@example.com']);
    $response = $this->putJson("/api/v2/identity/admin/users/{$target->id}/password", [
        'password' => 'AdminSet123!',
    ]);
    $response->assertStatus(403);
});
