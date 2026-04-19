<?php

declare(strict_types=1);

namespace Src\Billing\Application\Queries\ListStripeProductPrices;

use Src\Shared\Core\Bus\Query;

final readonly class ListStripeProductPricesQuery implements Query
{
    public function __construct(
        public string $productId,
    ) {}

    public function queryName(): string
    {
        return 'billing.stripe_product_prices.list';
    }
}
