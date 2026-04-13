<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\DeletePaymentMethod;

use Src\Billing\Domain\Ports\BillingPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;

final class DeletePaymentMethodHandler implements CommandHandler
{
    public function __construct(
        private readonly BillingPort $billing,
    ) {}

    public function handle(Command $command): null
    {
        assert($command instanceof DeletePaymentMethodCommand);

        if (! $this->billing->paymentMethodBelongsToUser($command->userId, $command->paymentMethodId)) {
            throw NotFound::entity('payment_method', $command->paymentMethodId);
        }

        if ($this->billing->hasActiveSubscriptions($command->userId)) {
            throw ValidationFailed::because('cannot_delete_payment_method_with_active_subscriptions');
        }

        $this->billing->deletePaymentMethod($command->userId, $command->paymentMethodId);

        return null;
    }
}
