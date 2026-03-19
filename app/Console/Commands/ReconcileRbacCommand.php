<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Src\Identity\Application\Commands\ReconcileRbacCatalog\ReconcileRbacCatalogCommand;
use Src\Shared\Core\Bus\CommandBus;

final class ReconcileRbacCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:reconcile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile the domain RBAC catalog with Spatie roles and permissions tables.';

    public function __construct(
        private readonly CommandBus $commandBus,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Reconciling RBAC catalog with Spatie tables...');

        $start = microtime(true);

        $this->commandBus->dispatch(new ReconcileRbacCatalogCommand());

        $elapsed = round((microtime(true) - $start) * 1000);

        $this->info("RBAC reconciliation complete in {$elapsed}ms.");

        return Command::SUCCESS;
    }
}
