<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\ReconcileRbacCatalog;

use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Domain\ValueObjects\RbacCatalog;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

/**
 * Reconciles the domain RBAC catalog with Spatie tables.
 *
 * The entire reconciliation is wrapped in a single database transaction via
 * RolePort::inTransaction(). Either ALL roles and permissions are synced
 * atomically, or NONE are (full rollback on any exception).
 *
 * For each entry in the catalog:
 *   1. Creates the role if it does not exist (idempotent via findOrCreate)
 *   2. Syncs the role's permissions to exactly match the catalog mapping
 *
 * Note: syncRolePermissions REPLACES the full permission set for a role —
 * it is not additive. Permissions manually added outside the catalog will be
 * removed on reconcile. This is by-design.
 *
 * Running this command multiple times with the same catalog produces the
 * same final state (idempotent).
 */
final class ReconcileRbacCatalogHandler implements CommandHandler
{
    public function __construct(
        private readonly RolePort $roles,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof ReconcileRbacCatalogCommand);

        $this->roles->inTransaction(function (): void {
            foreach (RbacCatalog::entries() as $entry) {
                $this->roles->createRole($entry->name);
                $this->roles->syncRolePermissions($entry->name, $entry->permissions);
            }
        });

        return null;
    }
}
