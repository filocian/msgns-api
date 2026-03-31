<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListAdminProducts;

use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class ListAdminProductsHandler implements QueryHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $repo,
    ) {}

    public function handle(Query $query): PaginatedResult
    {
        assert($query instanceof ListAdminProductsQuery);

        return $this->repo->listForAdmin([
            'page' => $query->page,
            'perPage' => $query->perPage,
            'sortBy' => $query->sortBy,
            'sortDir' => $query->sortDir,
            'productTypeCode' => $query->productTypeCode,
            'productTypeId' => $query->productTypeId,
            'model' => $query->model,
            'name' => $query->name,
            'userId' => $query->userId,
            'userEmail' => $query->userEmail,
            'assignedAtFrom' => $query->assignedAtFrom,
            'assignedAtTo' => $query->assignedAtTo,
            'configurationStatus' => $query->configurationStatus,
            'active' => $query->active,
            'targetUrl' => $query->targetUrl,
            'businessType' => $query->businessType,
            'businessSize' => $query->businessSize,
        ]);
    }
}
