<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Queries\GetSubscriptionType;

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Subscriptions\Application\Resources\SubscriptionTypeResource;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeNotFound;
use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;

final class GetSubscriptionTypeHandler implements QueryHandler
{
    public function __construct(
        private readonly SubscriptionTypeRepositoryPort $repo,
    ) {}

    public function handle(Query $query): SubscriptionTypeResource
    {
        assert($query instanceof GetSubscriptionTypeQuery);

        $subscriptionType = $this->repo->findById($query->id);

        if ($subscriptionType === null) {
            throw SubscriptionTypeNotFound::withId($query->id);
        }

        return SubscriptionTypeResource::fromEntity($subscriptionType);
    }
}
