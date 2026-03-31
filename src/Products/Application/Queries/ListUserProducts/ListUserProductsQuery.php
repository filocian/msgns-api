<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListUserProducts;

use Src\Shared\Core\Bus\Query;

final readonly class ListUserProductsQuery implements Query
{
    public function __construct(
        public int $userId,
        public int $page = 1,
        public int $perPage = 15,
        public string $sortBy = 'assigned_at',
        public string $sortDir = 'desc',
        public ?string $configurationStatus = null,
        public ?bool $active = null,
        public ?string $model = null,
        public ?string $targetUrl = null,
        public ?bool $hasBusinessInfo = null,
    ) {}

    public function queryName(): string
    {
        return 'products.list_user_products';
    }
}
