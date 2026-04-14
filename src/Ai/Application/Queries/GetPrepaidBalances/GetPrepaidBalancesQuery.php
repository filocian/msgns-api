<?php

declare(strict_types=1);

namespace Src\Ai\Application\Queries\GetPrepaidBalances;

use Src\Shared\Core\Bus\Query;

final readonly class GetPrepaidBalancesQuery implements Query
{
    public function __construct(
        public int $userId,
    ) {}

    public function queryName(): string
    {
        return 'ai.get_prepaid_balances';
    }
}
