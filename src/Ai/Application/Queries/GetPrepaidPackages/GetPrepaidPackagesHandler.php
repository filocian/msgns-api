<?php

declare(strict_types=1);

namespace Src\Ai\Application\Queries\GetPrepaidPackages;

use Illuminate\Database\Eloquent\Collection;
use Src\Ai\Infrastructure\Persistence\PrepaidPackageModel;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class GetPrepaidPackagesHandler implements QueryHandler
{
    /** @return Collection<int, PrepaidPackageModel> */
    public function handle(Query $query): Collection
    {
        assert($query instanceof GetPrepaidPackagesQuery);

        /** @var Collection<int, PrepaidPackageModel> */
        return PrepaidPackageModel::query()
            ->active()
            ->orderBy('price_cents', 'asc')
            ->get();
    }
}
