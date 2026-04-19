<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Queries\ListAdminSubscriptionTypes;

use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Subscriptions\Application\Resources\SubscriptionTypeListItemResource;
use Src\Subscriptions\Domain\Entities\SubscriptionType;
use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;

final class ListAdminSubscriptionTypesHandler implements QueryHandler
{
    public function __construct(
        private readonly SubscriptionTypeRepositoryPort $repo,
    ) {}

    public function handle(Query $query): PaginatedResult
    {
        assert($query instanceof ListAdminSubscriptionTypesQuery);

        $paginated = $this->repo->listAdmin(
            page: $query->page,
            perPage: $query->perPage,
            sortBy: $query->sortBy,
            sortDir: $query->sortDir,
            mode: $query->mode,
            isActive: $query->isActive,
        );

        $items = array_map(
            static fn (SubscriptionType $st): SubscriptionTypeListItemResource => SubscriptionTypeListItemResource::fromEntity($st),
            $paginated->items,
        );

        return new PaginatedResult(
            items: $items,
            currentPage: $paginated->currentPage,
            perPage: $paginated->perPage,
            total: $paginated->total,
            lastPage: $paginated->lastPage,
        );
    }
}
