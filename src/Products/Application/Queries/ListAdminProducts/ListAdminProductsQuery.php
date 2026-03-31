<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListAdminProducts;

use Src\Shared\Core\Bus\Query;

final readonly class ListAdminProductsQuery implements Query
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
        public string $sortBy = 'assigned_at',
        public string $sortDir = 'desc',
        public ?string $productTypeCode = null,
        public ?int $productTypeId = null,
        public ?string $model = null,
        public ?string $name = null,
        public ?int $userId = null,
        public ?string $userEmail = null,
        public ?string $assignedAtFrom = null,
        public ?string $assignedAtTo = null,
        public ?string $configurationStatus = null,
        public ?bool $active = null,
        public ?string $targetUrl = null,
        public ?string $businessType = null,
        public ?string $businessSize = null,
    ) {}

    public function queryName(): string
    {
        return 'products.list_admin_products';
    }
}
