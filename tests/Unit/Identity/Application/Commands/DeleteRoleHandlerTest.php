<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\DeleteRole\DeleteRoleCommand;
use Src\Identity\Application\Commands\DeleteRole\DeleteRoleHandler;
use Src\Identity\Domain\DTOs\RoleData;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Domain\Services\CoreRolePolicy;
use Src\Shared\Core\Errors\Unauthorized;

describe('DeleteRoleHandler', function () {

    beforeEach(function () {
        $this->roles          = Mockery::mock(RolePort::class);
        $this->coreRolePolicy = new CoreRolePolicy();
        $this->handler        = new DeleteRoleHandler($this->roles, $this->coreRolePolicy);
    });

    afterEach(fn() => Mockery::close());

    it('rejects deletion of core role developer with 403', function () {
        $this->roles->shouldReceive('findById')
            ->with(1)
            ->andReturn(new RoleData(id: 1, name: 'developer', permissions: [], usersCount: 0));

        $this->handler->handle(new DeleteRoleCommand(id: 1));
    })->throws(Unauthorized::class);

    it('rejects deletion of core role backoffice with 403', function () {
        $this->roles->shouldReceive('findById')
            ->with(2)
            ->andReturn(new RoleData(id: 2, name: 'backoffice', permissions: [], usersCount: 0));

        $this->handler->handle(new DeleteRoleCommand(id: 2));
    })->throws(Unauthorized::class);

    it('rejects deletion of core role user with 403', function () {
        $this->roles->shouldReceive('findById')
            ->with(5)
            ->andReturn(new RoleData(id: 5, name: 'user', permissions: [], usersCount: 0));

        $this->handler->handle(new DeleteRoleCommand(id: 5));
    })->throws(Unauthorized::class);

    it('allows deletion of non-core role designer', function () {
        $this->roles->shouldReceive('findById')
            ->with(3)
            ->andReturn(new RoleData(id: 3, name: 'designer', permissions: [], usersCount: 0));

        $this->roles->shouldReceive('deleteRole')->with(3)->once();

        $result = $this->handler->handle(new DeleteRoleCommand(id: 3));

        expect($result)->toBeNull();
    });

    it('allows deletion of non-core role marketing', function () {
        $this->roles->shouldReceive('findById')
            ->with(4)
            ->andReturn(new RoleData(id: 4, name: 'marketing', permissions: [], usersCount: 0));

        $this->roles->shouldReceive('deleteRole')->with(4)->once();

        $this->handler->handle(new DeleteRoleCommand(id: 4));
    });

    it('allows deletion of custom roles', function () {
        $this->roles->shouldReceive('findById')
            ->with(99)
            ->andReturn(new RoleData(id: 99, name: 'custom-role', permissions: [], usersCount: 0));

        $this->roles->shouldReceive('deleteRole')->with(99)->once();

        $this->handler->handle(new DeleteRoleCommand(id: 99));
    });

    it('does not call deleteRole when core role is targeted', function () {
        $this->roles->shouldReceive('findById')
            ->with(1)
            ->andReturn(new RoleData(id: 1, name: 'developer', permissions: [], usersCount: 0));

        $this->roles->shouldNotReceive('deleteRole');

        try {
            $this->handler->handle(new DeleteRoleCommand(id: 1));
        } catch (Unauthorized) {
            // Expected — verify deleteRole was never called
        }
    });
});
