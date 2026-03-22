<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\ReconcileRbacCatalog;

use Src\Shared\Core\Bus\Command;

final class ReconcileRbacCatalogCommand implements Command
{
    public function commandName(): string
    {
        return 'identity.reconcile_rbac_catalog';
    }
}
