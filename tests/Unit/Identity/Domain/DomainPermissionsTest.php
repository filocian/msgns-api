<?php

declare(strict_types=1);

use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Permissions\DomainRolePermissions;

describe('DomainPermissions', function () {

    it('REQ-01-A: MANAGE_ROLES_AND_PERMISSIONS constant has the correct value', function () {
        expect(DomainPermissions::MANAGE_ROLES_AND_PERMISSIONS)->toBe('manage_roles_and_permissions');
    });

    it('REQ-01-B: all() contains manage_roles_and_permissions and has exactly 33 elements', function () {
        $all = DomainPermissions::all();

        expect($all)->toContain('manage_roles_and_permissions');
        expect($all)->toHaveCount(33);
    });

    it('AI_FREE_PREVIEW constant has the correct value', function () {
        expect(DomainPermissions::AI_FREE_PREVIEW)->toBe('ai.free-preview');
    });

    it('all() contains ai.free-preview', function () {
        expect(DomainPermissions::all())->toContain('ai.free-preview');
    });

    it('BE-6: all 3 AI prepaid permissions are present in all()', function () {
        $all = DomainPermissions::all();

        expect($all)->toContain(DomainPermissions::AI_PREPAID_STARTER)
            ->and($all)->toContain(DomainPermissions::AI_PREPAID_GROWTH)
            ->and($all)->toContain(DomainPermissions::AI_PREPAID_PRO);
    });

    it('BE-6: AI prepaid permission constants have correct values', function () {
        expect(DomainPermissions::AI_PREPAID_STARTER)->toBe('ai.prepaid_starter')
            ->and(DomainPermissions::AI_PREPAID_GROWTH)->toBe('ai.prepaid_growth')
            ->and(DomainPermissions::AI_PREPAID_PRO)->toBe('ai.prepaid_pro');
    });

    it('BE-5: all 4 AI tier permissions are present in all()', function () {
        $all = DomainPermissions::all();

        expect($all)->toContain(DomainPermissions::AI_BASIC_MONTHLY)
            ->and($all)->toContain(DomainPermissions::AI_BASIC_YEARLY)
            ->and($all)->toContain(DomainPermissions::AI_STANDARD_MONTHLY)
            ->and($all)->toContain(DomainPermissions::AI_STANDARD_YEARLY);
    });

    it('BE-5: AI tier permission constants have correct values', function () {
        expect(DomainPermissions::AI_BASIC_MONTHLY)->toBe('ai.basic_monthly')
            ->and(DomainPermissions::AI_BASIC_YEARLY)->toBe('ai.basic_yearly')
            ->and(DomainPermissions::AI_STANDARD_MONTHLY)->toBe('ai.standard_monthly')
            ->and(DomainPermissions::AI_STANDARD_YEARLY)->toBe('ai.standard_yearly');
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
