<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Queries\GetSubscriptionType;

use Src\Shared\Core\Bus\Query;

final readonly class GetSubscriptionTypeQuery implements Query
{
    public function __construct(
        public int $id,
    ) {}

    public function queryName(): string
    {
        return 'subscriptions.get_subscription_type';
    }
}
