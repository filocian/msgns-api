<?php

declare(strict_types=1);

namespace Src\Billing\Application\Commands\CreateSetupIntent;

use Src\Billing\Application\Resources\SetupIntentResource;
use Src\Billing\Domain\Ports\BillingPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

final class CreateSetupIntentHandler implements CommandHandler
{
    public function __construct(
        private readonly BillingPort $billing,
    ) {}

    public function handle(Command $command): SetupIntentResource
    {
        assert($command instanceof CreateSetupIntentCommand);

        $this->billing->createOrGetCustomer($command->userId);
        $clientSecret = $this->billing->createSetupIntent($command->userId);

        return new SetupIntentResource(client_secret: $clientSecret);
    }
}
