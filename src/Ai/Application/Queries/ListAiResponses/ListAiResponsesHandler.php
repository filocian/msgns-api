<?php

declare(strict_types=1);

namespace Src\Ai\Application\Queries\ListAiResponses;

use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class ListAiResponsesHandler implements QueryHandler
{
    public function handle(Query $query): PaginatedResult
    {
        assert($query instanceof ListAiResponsesQuery);

        $builder = AiResponseRecordModel::query()
            ->where('user_id', $query->userId)
            ->orderBy('created_at', 'desc');

        if ($query->status !== null) {
            $builder->where('status', $query->status);
        }

        if ($query->productType !== null) {
            $builder->where('product_type', $query->productType);
        }

        $paginator = $builder->paginate(
            perPage: $query->perPage,
            page: $query->page,
        );

        return new PaginatedResult(
            items: $paginator->items(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }
}
