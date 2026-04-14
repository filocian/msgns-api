<?php

declare(strict_types=1);

namespace Src\Ai\Application\Queries\GetPrepaidBalances;

use Illuminate\Database\Eloquent\Collection;
use Src\Ai\Infrastructure\Persistence\UserPrepaidBalanceModel;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class GetPrepaidBalancesHandler implements QueryHandler
{
    /** @return Collection<int, UserPrepaidBalanceModel> */
    public function handle(Query $query): Collection
    {
        assert($query instanceof GetPrepaidBalancesQuery);

        /** @var Collection<int, UserPrepaidBalanceModel> */
        return UserPrepaidBalanceModel::query()
            ->where('user_id', $query->userId)
            ->withAvailableRequests()
            ->with('package')
            ->orderBy('purchased_at', 'asc')
            ->get();
    }
}
