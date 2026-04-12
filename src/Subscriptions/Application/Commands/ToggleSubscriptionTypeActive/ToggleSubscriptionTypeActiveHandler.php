<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Commands\ToggleSubscriptionTypeActive;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Subscriptions\Application\Resources\SubscriptionTypeResource;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeNotFound;
use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;

final class ToggleSubscriptionTypeActiveHandler implements CommandHandler
{
    public function __construct(
        private readonly SubscriptionTypeRepositoryPort $repo,
    ) {}

    public function handle(Command $command): SubscriptionTypeResource
    {
        assert($command instanceof ToggleSubscriptionTypeActiveCommand);

        $subscriptionType = $this->repo->findById($command->id);

        if ($subscriptionType === null) {
            throw SubscriptionTypeNotFound::withId($command->id);
        }

        $subscriptionType->toggleActive();

        $saved = $this->repo->save($subscriptionType);

        return SubscriptionTypeResource::fromEntity($saved);
    }
}
