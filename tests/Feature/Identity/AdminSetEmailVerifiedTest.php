<?php

declare(strict_types=1);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->artisan('rbac:reconcile')->assertExitCode(0);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

it('verifies an unverified user email', function () {
    $user = $this->create_user([
        'email'             => 'unverified@example.com',
        'email_verified_at' => null,
    ]);
    $response = $this->patchJson("/api/v2/identity/admin/users/{$user->id}/verify-email");
    $response->assertStatus(200)
             ->assertJsonPath('data.emailVerified', true);

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
});

it('is idempotent for already-verified user', function () {
    $user = $this->create_user([
        'email'             => 'verified@example.com',
        'email_verified_at' => now(),
    ]);
    $response = $this->patchJson("/api/v2/identity/admin/users/{$user->id}/verify-email");
    $response->assertStatus(200)
             ->assertJsonPath('data.emailVerified', true);
});

it('returns 404 for non-existent user', function () {
    $response = $this->patchJson('/api/v2/identity/admin/users/99999/verify-email');
    $response->assertStatus(404);
});

it('returns 403 for non-admin user', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $regular = $this->create_user(['email' => 'regular@example.com']);
    $this->actingAs($regular, 'stateful-api');

    $target = $this->create_user([
        'email'             => 'target@example.com',
        'email_verified_at' => null,
    ]);
    $response = $this->patchJson("/api/v2/identity/admin/users/{$target->id}/verify-email");
    $response->assertStatus(403);
});
