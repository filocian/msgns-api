<?php

declare(strict_types=1);

use Src\Identity\Domain\Permissions\DomainPermissions;

describe('DomainPermissions::descriptions()', function () {

    it('REQ-05-A: descriptions() count matches all() count', function () {
        expect(count(DomainPermissions::descriptions()))->toBe(count(DomainPermissions::all()));
    });

    it('REQ-05-A: every permission in all() has a key in descriptions()', function () {
        $descriptions = DomainPermissions::descriptions();

        foreach (DomainPermissions::all() as $permission) {
            expect(array_key_exists($permission, $descriptions))->toBeTrue(
                "Permission '{$permission}' is missing from descriptions()"
            );
        }
    });

    it('REQ-05-A: every description value is a non-empty string', function () {
        foreach (DomainPermissions::descriptions() as $permission => $description) {
            expect($description)->toBeString()
                ->and(strlen($description))->toBeGreaterThan(0, "Description for '{$permission}' is empty");
        }
    });

    it('REQ-05-B: manage_roles_and_permissions has a non-empty description', function () {
        $descriptions = DomainPermissions::descriptions();

        expect($descriptions)->toHaveKey('manage_roles_and_permissions');
        expect(strlen($descriptions['manage_roles_and_permissions']))->toBeGreaterThan(0);
    });
});
