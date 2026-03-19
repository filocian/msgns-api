<?php

declare(strict_types=1);

use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Permissions\DomainRolePermissions;
use Src\Identity\Domain\Permissions\DomainRoles;
use Src\Identity\Domain\ValueObjects\RbacCatalog;

describe('RbacCatalog parity with Domain* classes', function () {

    it('roles match domain roles', function () {
        $catalogRoles = collect(RbacCatalog::roleNames())->sort()->values()->toArray();
        $domainRoles = collect(DomainRoles::all())->sort()->values()->toArray();

        expect($catalogRoles)->toBe($domainRoles);
    });

    it('permissions match domain permissions', function () {
        $catalogPerms = collect(RbacCatalog::allPermissions())->sort()->values()->toArray();
        $domainPerms = collect(DomainPermissions::all())->sort()->values()->toArray();

        expect($catalogPerms)->toBe($domainPerms);
    });

    it('role permission mappings match domain role permissions', function () {
        $domainRoleMappings = [
            DomainRolePermissions::devRolePermissions(),
            DomainRolePermissions::backOfficeRolePermissions(),
            DomainRolePermissions::designerRolePermissions(),
            DomainRolePermissions::marketingRolePermissions(),
            DomainRolePermissions::userRolePermissions(),
        ];

        $domainByRole = [];
        foreach ($domainRoleMappings as $mapping) {
            $domainByRole[$mapping['role']] = collect($mapping['permissions'])->sort()->values()->toArray();
        }

        foreach (RbacCatalog::entries() as $entry) {
            $catalogPerms = collect($entry->permissions)->sort()->values()->toArray();

            expect($catalogPerms)->toBe(
                $domainByRole[$entry->name],
                "Permissions mismatch for role: {$entry->name}"
            );
        }
    });

    it('default role matches domain user role', function () {
        expect(RbacCatalog::defaultRole())->toBe(DomainRoles::USER_ROLE);
    });
});
