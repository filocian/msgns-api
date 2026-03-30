<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ResolveProductRedirection;

use Src\Shared\Core\Bus\Query;

final readonly class ResolveProductRedirectionQuery implements Query
{
    public function __construct(
        public int $productId,
        public string $password,
        public string $browserLocales,
    ) {}

    public function queryName(): string
    {
        return 'products.resolve_product_redirection';
    }
}
