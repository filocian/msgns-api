<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\HandlePrepaidPaymentFailed;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

/**
 * No pending balance row to rollback — graceful ignore for all cases.
 *
 * Payment failures for prepaid packages result in no DB state being created
 * (balance rows are only created on success). This handler exists for
 * completeness and to allow the webhook event to be recorded.
 */
final class HandlePrepaidPaymentFailedHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof HandlePrepaidPaymentFailedCommand);

        // No pending row to rollback — graceful ignore
        return null;
    }
}
