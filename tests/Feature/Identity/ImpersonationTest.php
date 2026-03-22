<?php

declare(strict_types=1);



beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = $this->createRole('developer');
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

it('starts impersonation of a regular user', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $target = $this->create_user(['email' => 'target@example.com']);
    $this->createRole('user_role');
    $target->assignRole('user_role');

    $response = $this->postWithHeaders("/api/v2/identity/impersonate/{$target->id}");
    $response->assertStatus(200);
});

it('returns 403 when trying to impersonate an admin', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $otherAdmin = $this->create_user(['email' => 'otheradmin@example.com']);
    $adminRole = $this->createRole('backoffice');
    $otherAdmin->assignRole($adminRole);

    $response = $this->postWithHeaders("/api/v2/identity/impersonate/{$otherAdmin->id}");
    $response->assertStatus(403);
});
