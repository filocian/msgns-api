<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListProductTypes;

use Src\Shared\Core\Bus\Query;

final readonly class ListProductTypesQuery implements Query
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
        public string $sortBy = 'name',
        public string $sortDir = 'asc',
    ) {}

    public function queryName(): string
    {
        return 'products.list_product_types';
    }
}
