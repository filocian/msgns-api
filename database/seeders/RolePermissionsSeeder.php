<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Src\Identity\Application\Commands\ReconcileRbacCatalog\ReconcileRbacCatalogCommand;
use Src\Shared\Core\Bus\CommandBus;

final class RolePermissionsSeeder extends Seeder
{
    protected string $table = 'product_types';

    /**
     * Run the database seeds.
     *
     * Delegates to the domain ReconcileRbacCatalogCommand so that the seeder
     * and the artisan rbac:reconcile command share the exact same logic.
     * This guarantees parity between fresh database setup and incremental
     * reconciliation runs.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /** @var CommandBus $commandBus */
        $commandBus = app(CommandBus::class);
        $commandBus->dispatch(new ReconcileRbacCatalogCommand());
    }
}
