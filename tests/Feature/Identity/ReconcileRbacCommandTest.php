<?php

declare(strict_types=1);

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Src\Identity\Domain\ValueObjects\RbacCatalog;

/**
 * Feature tests for the rbac:reconcile artisan command.
 *
 * Verifies FR-001 (catalog parity) and FR-005 (idempotency) end-to-end.
 */

describe('artisan rbac:reconcile', function () {

    beforeEach(function () {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    });

    it('seeds all 5 roles into Spatie tables with stateful-api guard', function () {
        $this->artisan('rbac:reconcile')->assertExitCode(0);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (RbacCatalog::roleNames() as $roleName) {
            $role = Role::where('name', $roleName)
                ->where('guard_name', 'stateful-api')
                ->first();

            expect($role)->not->toBeNull("Role '{$roleName}' should exist after reconciliation");
        }
    });

    it('seeds all 25 permissions into Spatie tables with stateful-api guard', function () {
        $this->artisan('rbac:reconcile')->assertExitCode(0);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (RbacCatalog::allPermissions() as $permName) {
            $perm = Permission::where('name', $permName)
                ->where('guard_name', 'stateful-api')
                ->first();

            expect($perm)->not->toBeNull("Permission '{$permName}' should exist after reconciliation");
        }
    });

    it('assigns all 25 permissions to developer role', function () {
        $this->artisan('rbac:reconcile')->assertExitCode(0);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();

        expect($role->permissions)->toHaveCount(25);
    });

    it('assigns all 25 permissions to backoffice role', function () {
        $this->artisan('rbac:reconcile')->assertExitCode(0);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::where('name', 'backoffice')->where('guard_name', 'stateful-api')->first();

        expect($role->permissions)->toHaveCount(25);
    });

    it('assigns only export_data to designer role', function () {
        $this->artisan('rbac:reconcile')->assertExitCode(0);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::where('name', 'designer')->where('guard_name', 'stateful-api')->first();

        expect($role->permissions)->toHaveCount(1)
            ->and($role->permissions->first()->name)->toBe('export_data');
    });

    it('assigns only export_data to marketing role', function () {
        $this->artisan('rbac:reconcile')->assertExitCode(0);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::where('name', 'marketing')->where('guard_name', 'stateful-api')->first();

        expect($role->permissions)->toHaveCount(1)
            ->and($role->permissions->first()->name)->toBe('export_data');
    });

    it('assigns correct 8 permissions to user role', function () {
        $this->artisan('rbac:reconcile')->assertExitCode(0);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::where('name', 'user')->where('guard_name', 'stateful-api')->first();

        expect($role->permissions)->toHaveCount(8);
    });

    it('is idempotent — running twice does not change the state', function () {
        $this->artisan('rbac:reconcile')->assertExitCode(0);
        $this->artisan('rbac:reconcile')->assertExitCode(0);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Roles count should be exactly 5 (no duplicates)
        $roleCount = Role::where('guard_name', 'stateful-api')->count();
        expect($roleCount)->toBe(5);

        // Permissions count should be exactly 25 (no duplicates)
        $permCount = Permission::where('guard_name', 'stateful-api')->count();
        expect($permCount)->toBe(25);
    });

    it('exits with success code', function () {
        $this->artisan('rbac:reconcile')->assertExitCode(0);
    });
});
