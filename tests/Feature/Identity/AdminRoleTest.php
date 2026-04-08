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

it('lists roles', function () {
    $response = $this->getJson('/api/v2/identity/admin/roles');
    $response->assertStatus(200)
             ->assertJsonStructure(['data']);
});

it('creates a new role', function () {
    $response = $this->postJson('/api/v2/identity/admin/roles', ['name' => 'custom_role']);
    $response->assertStatus(201)
             ->assertJsonPath('data.name', 'custom_role');
});

it('lists permissions', function () {
    $response = $this->getJson('/api/v2/identity/admin/permissions');
    $response->assertStatus(200);
});
