<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\SetDefaultPaymentMethod;

use Src\Billing\Application\Resources\PaymentMethodResource;
use Src\Billing\Domain\Ports\BillingPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class SetDefaultPaymentMethodHandler implements CommandHandler
{
    public function __construct(
        private readonly BillingPort $billing,
    ) {}

    public function handle(Command $command): PaymentMethodResource
    {
        assert($command instanceof SetDefaultPaymentMethodCommand);

        if (! $this->billing->paymentMethodBelongsToUser($command->userId, $command->paymentMethodId)) {
            throw NotFound::entity('payment_method', $command->paymentMethodId);
        }

        $this->billing->setDefaultPaymentMethod($command->userId, $command->paymentMethodId);

        return new PaymentMethodResource(
            id: $command->paymentMethodId,
            brand: '',
            last_four: '',
            exp_month: 0,
            exp_year: 0,
            is_default: true,
        );
    }
}
