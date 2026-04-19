<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Commands\DeleteSubscriptionType;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeHasActiveSubscriptions;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeNotFound;
use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;

final class DeleteSubscriptionTypeHandler implements CommandHandler
{
    public function __construct(
        private readonly SubscriptionTypeRepositoryPort $repo,
    ) {}

    public function handle(Command $command): null
    {
        assert($command instanceof DeleteSubscriptionTypeCommand);

        $subscriptionType = $this->repo->findById($command->id);

        if ($subscriptionType === null) {
            throw SubscriptionTypeNotFound::withId($command->id);
        }

        if ($this->repo->hasActiveSubscriptions($command->id)) {
            throw SubscriptionTypeHasActiveSubscriptions::forType($command->id);
        }

        $this->repo->softDelete($command->id);

        return null;
    }
}
