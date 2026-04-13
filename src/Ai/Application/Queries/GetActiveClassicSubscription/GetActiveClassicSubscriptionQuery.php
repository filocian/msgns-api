<?php

declare(strict_types=1);

namespace Src\Ai\Application\Queries\GetActiveClassicSubscription;

use Src\Shared\Core\Bus\Query;

final readonly class GetActiveClassicSubscriptionQuery implements Query
{
    public function __construct(
        public int $userId,
    ) {}

    public function queryName(): string
    {
        return 'ai.get_active_classic_subscription';
    }
}
