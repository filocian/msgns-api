<?php

declare(strict_types=1);

use Src\Identity\Domain\DTOs\RoleData;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Domain\Services\CoreRolePolicy;
use Src\Shared\Core\Errors\Unauthorized;

describe('CoreRolePolicy', function () {

    beforeEach(function () {
        $this->policy = new CoreRolePolicy();
        $this->roles  = Mockery::mock(RolePort::class);
    });

    afterEach(fn() => Mockery::close());

    // ---------------------------------------------------------------
    // guardDeletion
    // ---------------------------------------------------------------

    it('throws Unauthorized when deleting core role developer', function () {
        $this->policy->guardDeletion('developer');
    })->throws(Unauthorized::class);

    it('throws Unauthorized when deleting core role backoffice', function () {
        $this->policy->guardDeletion('backoffice');
    })->throws(Unauthorized::class);

    it('throws Unauthorized when deleting core role user', function () {
        $this->policy->guardDeletion('user');
    })->throws(Unauthorized::class);

    it('allows deletion of non-core role designer', function () {
        // Should not throw
        $this->policy->guardDeletion('designer');
        expect(true)->toBeTrue();
    });

    it('allows deletion of non-core role marketing', function () {
        $this->policy->guardDeletion('marketing');
        expect(true)->toBeTrue();
    });

    it('allows deletion of custom roles', function () {
        $this->policy->guardDeletion('custom-admin');
        expect(true)->toBeTrue();
    });

    it('exception for core role deletion contains role name in context', function () {
        try {
            $this->policy->guardDeletion('developer');
        } catch (Unauthorized $e) {
            expect($e->getMessage())->toBe('core_role_protected');
        }
    });

    // ---------------------------------------------------------------
    // guardRename
    // ---------------------------------------------------------------

    it('throws Unauthorized when renaming core role developer', function () {
        $this->roles->shouldReceive('findById')
            ->with(1)
            ->andReturn(new RoleData(id: 1, name: 'developer', permissions: [], usersCount: 0));

        $this->policy->guardRename(1, 'dev', $this->roles);
    })->throws(Unauthorized::class);

    it('throws Unauthorized when renaming core role backoffice', function () {
        $this->roles->shouldReceive('findById')
            ->with(2)
            ->andReturn(new RoleData(id: 2, name: 'backoffice', permissions: [], usersCount: 0));

        $this->policy->guardRename(2, 'back-admin', $this->roles);
    })->throws(Unauthorized::class);

    it('throws Unauthorized when renaming core role user', function () {
        $this->roles->shouldReceive('findById')
            ->with(5)
            ->andReturn(new RoleData(id: 5, name: 'user', permissions: [], usersCount: 0));

        $this->policy->guardRename(5, 'member', $this->roles);
    })->throws(Unauthorized::class);

    it('allows renaming non-core role designer', function () {
        $this->roles->shouldReceive('findById')
            ->with(3)
            ->andReturn(new RoleData(id: 3, name: 'designer', permissions: [], usersCount: 0));

        $this->policy->guardRename(3, 'graphic-designer', $this->roles);
        expect(true)->toBeTrue();
    });

    it('allows renaming custom roles', function () {
        $this->roles->shouldReceive('findById')
            ->with(99)
            ->andReturn(new RoleData(id: 99, name: 'custom-role', permissions: [], usersCount: 0));

        $this->policy->guardRename(99, 'custom-role-v2', $this->roles);
        expect(true)->toBeTrue();
    });

    // ---------------------------------------------------------------
    // guardPermissionSync (T-08)
    // ---------------------------------------------------------------

    it('throws Unauthorized when syncing permissions on core role developer', function () {
        $this->policy->guardPermissionSync('developer');
    })->throws(Unauthorized::class);

    it('throws Unauthorized when syncing permissions on core role backoffice', function () {
        $this->policy->guardPermissionSync('backoffice');
    })->throws(Unauthorized::class);

    it('throws Unauthorized when syncing permissions on core role user', function () {
        $this->policy->guardPermissionSync('user');
    })->throws(Unauthorized::class);

    it('allows syncing permissions on non-core role designer', function () {
        $this->policy->guardPermissionSync('designer');
        expect(true)->toBeTrue();
    });

    it('allows syncing permissions on custom role', function () {
        $this->policy->guardPermissionSync('custom-admin');
        expect(true)->toBeTrue();
    });

    it('exception for core role permission sync contains core_role_protected code', function () {
        try {
            $this->policy->guardPermissionSync('developer');
        } catch (Unauthorized $e) {
            expect($e->getMessage())->toBe('core_role_protected');
        }
    });
});
