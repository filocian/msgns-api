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

it('updates user with email + phone + country + default_locale', function () {
    $user = $this->create_user(['email' => 'target@example.com']);
    $response = $this->patchJson("/api/v2/identity/admin/users/{$user->id}", [
        'email'          => 'new@example.com',
        'phone'          => '+34 600-1234',
        'country'        => 'ES',
        'default_locale' => 'es',
    ]);
    $response->assertStatus(200)
             ->assertJsonPath('data.email', 'new@example.com')
             ->assertJsonPath('data.phone', '+34 600-1234')
             ->assertJsonPath('data.country', 'ES')
             ->assertJsonPath('data.defaultLocale', 'es');
});

it('returns 409 on email conflict', function () {
    $this->create_user(['email' => 'taken@example.com']);
    $target = $this->create_user(['email' => 'target@example.com']);
    $response = $this->patchJson("/api/v2/identity/admin/users/{$target->id}", [
        'email' => 'taken@example.com',
    ]);
    $response->assertStatus(409)
             ->assertJsonPath('error.code', 'email_already_taken');
});

it('allows updating to the same email (no conflict)', function () {
    $user = $this->create_user(['email' => 'same@example.com']);
    $response = $this->patchJson("/api/v2/identity/admin/users/{$user->id}", [
        'email' => 'same@example.com',
    ]);
    $response->assertStatus(200);
});

it('returns 404 for non-existent user', function () {
    $response = $this->patchJson('/api/v2/identity/admin/users/99999', [
        'name' => 'Ghost',
    ]);
    $response->assertStatus(404);
});

it('returns 403 for non-admin user', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $regular = $this->create_user(['email' => 'regular@example.com']);
    $this->actingAs($regular, 'stateful-api');

    $target = $this->create_user(['email' => 'target2@example.com']);
    $response = $this->patchJson("/api/v2/identity/admin/users/{$target->id}", [
        'name' => 'Hacked',
    ]);
    $response->assertStatus(403);
});
