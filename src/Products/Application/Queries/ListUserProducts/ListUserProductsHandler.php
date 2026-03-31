<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListUserProducts;

use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class ListUserProductsHandler implements QueryHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $repo,
    ) {}

    public function handle(Query $query): PaginatedResult
    {
        assert($query instanceof ListUserProductsQuery);

        return $this->repo->listForUser([
            'userId' => $query->userId,
            'page' => $query->page,
            'perPage' => $query->perPage,
            'sortBy' => $query->sortBy,
            'sortDir' => $query->sortDir,
            'configurationStatus' => $query->configurationStatus,
            'active' => $query->active,
            'model' => $query->model,
            'targetUrl' => $query->targetUrl,
            'hasBusinessInfo' => $query->hasBusinessInfo,
        ]);
    }
}
