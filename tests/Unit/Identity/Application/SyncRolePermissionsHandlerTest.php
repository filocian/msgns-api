<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\SyncRolePermissions\SyncRolePermissionsCommand;
use Src\Identity\Application\Commands\SyncRolePermissions\SyncRolePermissionsHandler;
use Src\Identity\Application\Resources\RoleResource;
use Src\Identity\Domain\DTOs\RoleData;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Domain\Services\CoreRolePolicy;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\Unauthorized;
use Src\Shared\Core\Errors\ValidationFailed;

describe('SyncRolePermissionsHandler', function () {

    beforeEach(function () {
        $this->roles          = Mockery::mock(RolePort::class);
        $this->coreRolePolicy = new CoreRolePolicy();
        $this->handler        = new SyncRolePermissionsHandler($this->roles, $this->coreRolePolicy);
    });

    afterEach(fn() => Mockery::close());

    it('syncs valid permissions on a custom role and returns RoleResource', function () {
        $roleData = new RoleData(id: 10, name: 'custom-role', permissions: [], usersCount: 0);
        $updatedRoleData = new RoleData(id: 10, name: 'custom-role', permissions: ['create_role', 'edit_role'], usersCount: 0);

        $this->roles->shouldReceive('findById')
            ->with(10)
            ->twice()
            ->andReturn($roleData, $updatedRoleData);

        $this->roles->shouldReceive('syncPermissionsByRoleId')
            ->with(10, ['create_role', 'edit_role'])
            ->once();

        $result = $this->handler->handle(new SyncRolePermissionsCommand(
            roleId: 10,
            permissions: ['create_role', 'edit_role'],
        ));

        expect($result)->toBeInstanceOf(RoleResource::class);
        expect($result->permissions)->toBe(['create_role', 'edit_role']);
    });

    it('rejects syncing permissions on core role developer with Unauthorized', function () {
        $this->roles->shouldReceive('findById')
            ->with(1)
            ->andReturn(new RoleData(id: 1, name: 'developer', permissions: [], usersCount: 0));

        $this->roles->shouldNotReceive('syncPermissionsByRoleId');

        $this->handler->handle(new SyncRolePermissionsCommand(
            roleId: 1,
            permissions: ['create_role'],
        ));
    })->throws(Unauthorized::class);

    it('rejects syncing permissions on core role backoffice with Unauthorized', function () {
        $this->roles->shouldReceive('findById')
            ->with(2)
            ->andReturn(new RoleData(id: 2, name: 'backoffice', permissions: [], usersCount: 0));

        $this->roles->shouldNotReceive('syncPermissionsByRoleId');

        $this->handler->handle(new SyncRolePermissionsCommand(
            roleId: 2,
            permissions: ['create_role'],
        ));
    })->throws(Unauthorized::class);

    it('throws ValidationFailed for unknown permission names', function () {
        $this->roles->shouldReceive('findById')
            ->with(10)
            ->andReturn(new RoleData(id: 10, name: 'custom-role', permissions: [], usersCount: 0));

        $this->roles->shouldNotReceive('syncPermissionsByRoleId');

        $this->handler->handle(new SyncRolePermissionsCommand(
            roleId: 10,
            permissions: ['create_role', 'nonexistent_permission'],
        ));
    })->throws(ValidationFailed::class);

    it('bubbles NotFound when role does not exist', function () {
        $this->roles->shouldReceive('findById')
            ->with(99999)
            ->andThrow(NotFound::because('role_not_found'));

        $this->handler->handle(new SyncRolePermissionsCommand(
            roleId: 99999,
            permissions: ['create_role'],
        ));
    })->throws(NotFound::class);

    it('allows syncing empty permissions array on custom role', function () {
        $roleData = new RoleData(id: 10, name: 'custom-role', permissions: ['create_role'], usersCount: 0);
        $updatedRoleData = new RoleData(id: 10, name: 'custom-role', permissions: [], usersCount: 0);

        $this->roles->shouldReceive('findById')
            ->with(10)
            ->twice()
            ->andReturn($roleData, $updatedRoleData);

        $this->roles->shouldReceive('syncPermissionsByRoleId')
            ->with(10, [])
            ->once();

        $result = $this->handler->handle(new SyncRolePermissionsCommand(
            roleId: 10,
            permissions: [],
        ));

        expect($result)->toBeInstanceOf(RoleResource::class);
        expect($result->permissions)->toBe([]);
    });
});
