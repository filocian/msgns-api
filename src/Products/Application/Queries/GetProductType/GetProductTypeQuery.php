<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\GetProductType;

use Src\Shared\Core\Bus\Query;

final readonly class GetProductTypeQuery implements Query
{
    public function __construct(
        public int $productTypeId,
    ) {}

    public function queryName(): string
    {
        return 'products.get_product_type';
    }
}
