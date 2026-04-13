<?php

declare(strict_types=1);

namespace Src\Ai\Application\Queries\GetActiveClassicSubscription;

use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class GetActiveClassicSubscriptionHandler implements QueryHandler
{
    public function handle(Query $query): ?UserSubscriptionModel
    {
        assert($query instanceof GetActiveClassicSubscriptionQuery);

        /** @var UserSubscriptionModel|null */
        return UserSubscriptionModel::query()
            ->with('subscriptionType')
            ->where('user_id', $query->userId)
            ->whereIn('status', ['active', 'cancelled'])
            ->first();
    }
}
