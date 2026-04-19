<?php

declare(strict_types=1);

use Src\Identity\Domain\ValueObjects\RbacCatalog;
use Src\Identity\Domain\ValueObjects\RoleCatalogEntry;

describe('RbacCatalog', function () {

    it('exposes exactly 5 roles', function () {
        $entries = RbacCatalog::entries();

        expect($entries)->toHaveCount(5);
    });

    it('contains all legacy role names', function () {
        $names = RbacCatalog::roleNames();

        expect($names)->toContain('developer')
            ->and($names)->toContain('backoffice')
            ->and($names)->toContain('designer')
            ->and($names)->toContain('marketing')
            ->and($names)->toContain('user');
    });

    it('exposes exactly 33 permissions', function () {
        expect(RbacCatalog::allPermissions())->toHaveCount(33);
    });

    it('marks developer, backoffice, and user as core roles', function () {
        $entries = collect(RbacCatalog::entries())->keyBy('name');

        expect($entries['developer']->isCore)->toBeTrue()
            ->and($entries['backoffice']->isCore)->toBeTrue()
            ->and($entries['user']->isCore)->toBeTrue();
    });

    it('marks designer and marketing as non-core roles', function () {
        $entries = collect(RbacCatalog::entries())->keyBy('name');

        expect($entries['designer']->isCore)->toBeFalse()
            ->and($entries['marketing']->isCore)->toBeFalse();
    });

    it('assigns all 33 permissions to developer role', function () {
        $entries = collect(RbacCatalog::entries())->keyBy('name');

        expect($entries['developer']->permissions)->toHaveCount(33);
    });

    it('assigns all 33 permissions to backoffice role', function () {
        $entries = collect(RbacCatalog::entries())->keyBy('name');

        expect($entries['backoffice']->permissions)->toHaveCount(33);
    });

    it('assigns only export_data to designer role', function () {
        $entries = collect(RbacCatalog::entries())->keyBy('name');

        expect($entries['designer']->permissions)->toEqual(['export_data']);
    });

    it('assigns only export_data to marketing role', function () {
        $entries = collect(RbacCatalog::entries())->keyBy('name');

        expect($entries['marketing']->permissions)->toEqual(['export_data']);
    });

    it('assigns the correct 8 permissions to user role', function () {
        $entries = collect(RbacCatalog::entries())->keyBy('name');

        expect($entries['user']->permissions)->toHaveCount(8)
            ->and($entries['user']->permissions)->toContain('single_product_activation')
            ->and($entries['user']->permissions)->toContain('single_product_deactivation')
            ->and($entries['user']->permissions)->toContain('single_product_assignment')
            ->and($entries['user']->permissions)->toContain('single_product_configuration')
            ->and($entries['user']->permissions)->toContain('create_business')
            ->and($entries['user']->permissions)->toContain('edit_business')
            ->and($entries['user']->permissions)->toContain('single_product_business_assignment')
            ->and($entries['user']->permissions)->toContain('edit_user');
    });

    it('returns user as the default role', function () {
        expect(RbacCatalog::defaultRole())->toBe('user');
    });

    it('returns correct core role names list', function () {
        $coreRoles = RbacCatalog::coreRoleNames();

        expect($coreRoles)->toContain('developer')
            ->and($coreRoles)->toContain('backoffice')
            ->and($coreRoles)->toContain('user')
            ->and($coreRoles)->not->toContain('designer')
            ->and($coreRoles)->not->toContain('marketing');
    });

    it('entries return RoleCatalogEntry instances', function () {
        foreach (RbacCatalog::entries() as $entry) {
            expect($entry)->toBeInstanceOf(RoleCatalogEntry::class);
        }
    });

    it('permission names match DomainPermissions::all() exactly', function () {
        $allPerms = RbacCatalog::allPermissions();

        $expectedPermissions = [
            'create_product_type',
            'edit_product_type',
            'single_product_activation',
            'single_product_deactivation',
            'bulk_product_activation',
            'bulk_product_deactivation',
            'single_product_assignment',
            'bulk_product_assignment',
            'single_product_configuration',
            'bulk_product_configuration',
            'create_business',
            'edit_business',
            'single_product_business_assignment',
            'bulk_product_business_assignment',
            'product_generation',
            'create_role',
            'edit_role',
            'assign_role',
            'create_permission',
            'edit_permission',
            'assign_permission',
            'edit_user',
            'export_data',
            'manage_roles_and_permissions',
            'manage_subscription_types',
            'ai.free-preview',
            'ai.basic_monthly',
            'ai.basic_yearly',
            'ai.standard_monthly',
            'ai.standard_yearly',
            'ai.prepaid_starter',
            'ai.prepaid_growth',
            'ai.prepaid_pro',
        ];

        expect($allPerms)->toEqual($expectedPermissions);
    });
});
