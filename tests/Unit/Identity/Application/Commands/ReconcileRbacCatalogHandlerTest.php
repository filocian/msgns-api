<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\ReconcileRbacCatalog\ReconcileRbacCatalogCommand;
use Src\Identity\Application\Commands\ReconcileRbacCatalog\ReconcileRbacCatalogHandler;
use Src\Identity\Domain\DTOs\RoleData;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Domain\ValueObjects\RbacCatalog;

describe('ReconcileRbacCatalogHandler', function () {

    beforeEach(function () {
        $this->roles   = Mockery::mock(RolePort::class);
        $this->handler = new ReconcileRbacCatalogHandler($this->roles);

        // Default inTransaction mock: execute the callable normally
        $this->roles->shouldReceive('inTransaction')
            ->byDefault()
            ->andReturnUsing(fn(callable $cb) => $cb());
    });

    afterEach(fn() => Mockery::close());

    it('calls createRole for each catalog entry', function () {
        $entries = RbacCatalog::entries();

        $this->roles->shouldReceive('inTransaction')
            ->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        foreach ($entries as $entry) {
            $this->roles->shouldReceive('createRole')
                ->with($entry->name)
                ->once()
                ->andReturn(new RoleData(id: 1, name: $entry->name, permissions: [], usersCount: 0));

            $this->roles->shouldReceive('syncRolePermissions')
                ->with($entry->name, $entry->permissions)
                ->once();
        }

        $this->handler->handle(new ReconcileRbacCatalogCommand());
    });

    it('calls syncRolePermissions for each catalog entry with correct permissions', function () {
        $developer = collect(RbacCatalog::entries())->firstWhere('name', 'developer');

        $this->roles->shouldReceive('inTransaction')
            ->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        // Allow all 5 role calls
        $this->roles->shouldReceive('createRole')->andReturn(
            new RoleData(id: 1, name: 'developer', permissions: [], usersCount: 0)
        );

        // Specific expectation must come before the generic catch-all so Mockery
        // matches it first when the developer entry is processed.
        $this->roles->shouldReceive('syncRolePermissions')
            ->with('developer', $developer->permissions)
            ->once();

        // Allow remaining 4 non-developer syncRolePermissions calls
        $this->roles->shouldReceive('syncRolePermissions')
            ->withAnyArgs();

        $this->handler->handle(new ReconcileRbacCatalogCommand());
    });

    it('creates exactly 5 roles (one per catalog entry)', function () {
        $this->roles->shouldReceive('inTransaction')
            ->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        $this->roles->shouldReceive('createRole')
            ->times(5)
            ->andReturn(new RoleData(id: 1, name: 'any', permissions: [], usersCount: 0));

        $this->roles->shouldReceive('syncRolePermissions')
            ->times(5);

        $this->handler->handle(new ReconcileRbacCatalogCommand());
    });

    it('returns null (command has no return value)', function () {
        $this->roles->shouldReceive('inTransaction')
            ->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        $this->roles->shouldReceive('createRole')->andReturn(
            new RoleData(id: 1, name: 'any', permissions: [], usersCount: 0)
        );
        $this->roles->shouldReceive('syncRolePermissions');

        $result = $this->handler->handle(new ReconcileRbacCatalogCommand());

        expect($result)->toBeNull();
    });

    it('is idempotent — running again produces same call count', function () {
        $this->roles->shouldReceive('inTransaction')
            ->twice()
            ->andReturnUsing(fn(callable $cb) => $cb());

        $this->roles->shouldReceive('createRole')->times(10)
            ->andReturn(new RoleData(id: 1, name: 'any', permissions: [], usersCount: 0));
        $this->roles->shouldReceive('syncRolePermissions')->times(10);

        $this->handler->handle(new ReconcileRbacCatalogCommand());
        $this->handler->handle(new ReconcileRbacCatalogCommand());
    });

    it('wraps all operations inside a single transaction', function () {
        $callOrder = [];

        $this->roles->shouldReceive('inTransaction')
            ->once()
            ->andReturnUsing(function (callable $cb) use (&$callOrder) {
                $callOrder[] = 'transaction_start';
                $cb();
                $callOrder[] = 'transaction_end';
            });

        $this->roles->shouldReceive('createRole')
            ->andReturnUsing(function (string $name) use (&$callOrder) {
                $callOrder[] = "createRole:{$name}";
                return new RoleData(id: 1, name: $name, permissions: [], usersCount: 0);
            });

        $this->roles->shouldReceive('syncRolePermissions')
            ->andReturnUsing(function (string $role) use (&$callOrder) {
                $callOrder[] = "syncPermissions:{$role}";
            });

        $this->handler->handle(new ReconcileRbacCatalogCommand());

        // All createRole/syncPermissions calls must be between transaction_start and transaction_end
        expect($callOrder[0])->toBe('transaction_start')
            ->and(end($callOrder))->toBe('transaction_end');
    });

    it('rolls back all changes if an exception occurs during reconciliation', function () {
        $this->roles->shouldReceive('inTransaction')
            ->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        $this->roles->shouldReceive('createRole')
            ->andReturn(new RoleData(id: 1, name: 'any', permissions: [], usersCount: 0));

        // The second syncRolePermissions call throws
        $callCount = 0;
        $this->roles->shouldReceive('syncRolePermissions')
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 2) {
                    throw new \RuntimeException('Simulated DB failure');
                }
            });

        expect(fn() => $this->handler->handle(new ReconcileRbacCatalogCommand()))
            ->toThrow(\RuntimeException::class, 'Simulated DB failure');
    });
});
