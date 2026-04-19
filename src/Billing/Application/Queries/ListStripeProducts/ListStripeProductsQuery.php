<?php

declare(strict_types=1);

namespace Src\Billing\Application\Queries\ListStripeProducts;

use Src\Shared\Core\Bus\Query;

final readonly class ListStripeProductsQuery implements Query
{
    public function __construct() {}

    public function queryName(): string
    {
        return 'billing.stripe_products.list';
    }
}
