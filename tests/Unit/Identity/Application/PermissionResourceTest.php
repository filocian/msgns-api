<?php

declare(strict_types=1);

use Src\Identity\Application\Resources\PermissionResource;

describe('PermissionResource', function () {

    it('REQ-07-A: serializes description when non-null', function () {
        $resource = new PermissionResource(
            id: 1,
            name: 'create_role',
            description: 'Allows creating new custom roles',
        );

        expect($resource->description)->toBe('Allows creating new custom roles');
    });

    it('REQ-07-B: description is null when not provided', function () {
        $resource = new PermissionResource(
            id: 1,
            name: 'create_role',
        );

        expect($resource->description)->toBeNull();
    });

    it('REQ-07-B: description is null when explicitly passed as null', function () {
        $resource = new PermissionResource(
            id: 2,
            name: 'edit_role',
            description: null,
        );

        expect($resource->description)->toBeNull();
    });

    it('id and name are correctly set', function () {
        $resource = new PermissionResource(
            id: 5,
            name: 'assign_role',
            description: 'Assign roles to users',
        );

        expect($resource->id)->toBe(5);
        expect($resource->name)->toBe('assign_role');
    });
});
