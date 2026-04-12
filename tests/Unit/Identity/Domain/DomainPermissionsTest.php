<?php

declare(strict_types=1);

use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Permissions\DomainRolePermissions;

describe('DomainPermissions', function () {

    it('REQ-01-A: MANAGE_ROLES_AND_PERMISSIONS constant has the correct value', function () {
        expect(DomainPermissions::MANAGE_ROLES_AND_PERMISSIONS)->toBe('manage_roles_and_permissions');
    });

    it('REQ-01-B: all() contains manage_roles_and_permissions and has exactly 25 elements', function () {
        $all = DomainPermissions::all();

        expect($all)->toContain('manage_roles_and_permissions');
        expect($all)->toHaveCount(25);
    });

    it('REQ-02-A: devRolePermissions contains manage_roles_and_permissions', function () {
        $devPerms = DomainRolePermissions::devRolePermissions();

        expect($devPerms['permissions'])->toContain('manage_roles_and_permissions');
    });

    it('REQ-02-B: backOfficeRolePermissions contains manage_roles_and_permissions', function () {
        $backofficePerms = DomainRolePermissions::backOfficeRolePermissions();

        expect($backofficePerms['permissions'])->toContain('manage_roles_and_permissions');
    });
});
