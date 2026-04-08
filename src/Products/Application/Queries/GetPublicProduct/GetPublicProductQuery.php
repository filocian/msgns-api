<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\GetPublicProduct;

use Src\Shared\Core\Bus\Query;

final readonly class GetPublicProductQuery implements Query
{
    public function __construct(
        public int $productId,
        public string $password,
    ) {}

    public function queryName(): string
    {
        return 'products.get_public_product';
    }
}
