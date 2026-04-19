<?php

declare(strict_types=1);

use App\Static\Permissions\StaticRoles;

/**
 * Integration tests verifying that legacy FormRequest authorization
 * continues to work correctly after rbac:reconcile runs (FR-003).
 *
 * The legacy GET /api/users/list-roles endpoint is protected by
 * ListRolesRequest::authorize() which checks hasAnyRole(['backoffice', 'developer'])
 * via Spatie. After reconciliation the Spatie tables must contain those roles
 * so the legacy check still resolves correctly.
 */
describe('Legacy FormRequest authorization after rbac:reconcile', function () {

    beforeEach(function () {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        // Reconcile runs first — this populates Spatie tables from the domain catalog.
        $this->artisan('rbac:reconcile')->assertExitCode(0);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    });

    it('allows backoffice user to access legacy list-roles endpoint after reconciliation', function () {
        $user = $this->create_user(['email' => 'backoffice-legacy@example.com']);
        $user->assignRole(StaticRoles::BACKOFFICE_ROLE);
        $this->actingAs($user, 'stateful-api');

        $this->getJson('/api/users/list-roles')
            ->assertStatus(200);
    })->group('legacy');

    it('allows developer user to access legacy list-roles endpoint after reconciliation', function () {
        $user = $this->create_user(['email' => 'developer-legacy@example.com']);
        $user->assignRole(StaticRoles::DEV_ROLE);
        $this->actingAs($user, 'stateful-api');

        $this->getJson('/api/users/list-roles')
            ->assertStatus(200);
    })->group('legacy');

    it('denies user role from accessing legacy list-roles endpoint after reconciliation', function () {
        $user = $this->create_user(['email' => 'user-legacy@example.com']);
        $user->assignRole(StaticRoles::USER_ROLE);
        $this->actingAs($user, 'stateful-api');

        // ActionNotAllowedException maps to 403 auth.forbidden in bootstrap/app.php
        $this->getJson('/api/users/list-roles')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'auth.forbidden');
    })->group('legacy');

    it('denies designer role from accessing legacy list-roles endpoint after reconciliation', function () {
        $user = $this->create_user(['email' => 'designer-legacy@example.com']);
        $user->assignRole(StaticRoles::DESIGNER_ROLE);
        $this->actingAs($user, 'stateful-api');

        // ActionNotAllowedException maps to 403 auth.forbidden in bootstrap/app.php
        $this->getJson('/api/users/list-roles')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'auth.forbidden');
    })->group('legacy');

    it('backoffice hasAnyRole check resolves correctly after running reconciliation twice (idempotent parity)', function () {
        // Run reconcile a second time to verify idempotency does not break auth.
        $this->artisan('rbac:reconcile')->assertExitCode(0);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user = $this->create_user(['email' => 'backoffice-idempotent@example.com']);
        $user->assignRole(StaticRoles::BACKOFFICE_ROLE);
        $this->actingAs($user, 'stateful-api');

        $this->getJson('/api/users/list-roles')
            ->assertStatus(200);
    })->group('legacy');
});
