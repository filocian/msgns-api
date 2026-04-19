<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Queries\ListPublicSubscriptionTypes;

use Src\Shared\Core\Bus\Query;

final readonly class ListPublicSubscriptionTypesQuery implements Query
{
    public function queryName(): string
    {
        return 'subscriptions.list_public_subscription_types';
    }
}
