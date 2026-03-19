<?php

declare(strict_types=1);

use App\Static\Permissions\StaticPermissions;
use App\Static\Permissions\StaticRolePermissions;
use App\Static\Permissions\StaticRoles;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Permissions\DomainRolePermissions;
use Src\Identity\Domain\Permissions\DomainRoles;

describe('Domain* vs Static* parity (drift detection)', function () {

    it('DomainRoles constants match StaticRoles constants', function () {
        expect(DomainRoles::DEV_ROLE)->toBe(StaticRoles::DEV_ROLE);
        expect(DomainRoles::BACKOFFICE_ROLE)->toBe(StaticRoles::BACKOFFICE_ROLE);
        expect(DomainRoles::DESIGNER_ROLE)->toBe(StaticRoles::DESIGNER_ROLE);
        expect(DomainRoles::MARKETING_ROLE)->toBe(StaticRoles::MARKETING_ROLE);
        expect(DomainRoles::USER_ROLE)->toBe(StaticRoles::USER_ROLE);
    });

    it('DomainRoles::all() matches StaticRoles::all()', function () {
        $domain = collect(DomainRoles::all())->sort()->values()->toArray();
        $static = collect(StaticRoles::all())->sort()->values()->toArray();

        expect($domain)->toBe($static);
    });

    it('DomainPermissions::all() matches StaticPermissions::all()', function () {
        $domain = collect(DomainPermissions::all())->sort()->values()->toArray();
        $static = collect(StaticPermissions::all())->sort()->values()->toArray();

        expect($domain)->toBe($static);
    });

    it('DomainRolePermissions methods match StaticRolePermissions methods', function () {
        $methods = [
            'devRolePermissions',
            'backOfficeRolePermissions',
            'designerRolePermissions',
            'marketingRolePermissions',
            'userRolePermissions',
        ];

        foreach ($methods as $method) {
            $domain = DomainRolePermissions::$method();
            $static = StaticRolePermissions::$method();

            expect($domain['role'])->toBe($static['role'], "Role mismatch in {$method}");

            $domainPerms = collect($domain['permissions'])->sort()->values()->toArray();
            $staticPerms = collect($static['permissions'])->sort()->values()->toArray();
            expect($domainPerms)->toBe($staticPerms, "Permissions mismatch in {$method}");
        }
    });
});
