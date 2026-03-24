<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListProductTypes;

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Entities\ProductType;
use Src\Products\Application\Resources\ProductTypeListItemResource;

final class ListProductTypesHandler implements QueryHandler
{
    public function __construct(
        private readonly ProductTypeRepository $repo,
    ) {}

    public function handle(Query $query): PaginatedResult
    {
        assert($query instanceof ListProductTypesQuery);

        $paginated = $this->repo->list([
            'page'    => $query->page,
            'perPage' => $query->perPage,
            'sortBy'  => $query->sortBy,
            'sortDir' => $query->sortDir,
        ]);

        $items = array_map(
            static fn (ProductType $productType): ProductTypeListItemResource => new ProductTypeListItemResource(
                id: $productType->id,
                code: $productType->code->value,
                name: $productType->name,
                primaryModel: $productType->models->primary,
                secondaryModel: $productType->models->secondary,
                createdAt: $productType->createdAt->format('c'),
                updatedAt: $productType->updatedAt->format('c'),
            ),
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
