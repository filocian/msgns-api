<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Ports;

use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;
use Src\Billing\Domain\Errors\StripeProductUnavailable;

interface StripeCatalogPort
{
    /**
     * @return list<StripeCatalogProduct>
     */
    public function listProducts(): array;

    /**
     * @throws StripeProductUnavailable if the product does not exist or is inactive.
     */
    public function getProduct(string $productId): StripeCatalogProduct;

    /**
     * @return list<StripeCatalogPrice>
     */
    public function listPricesForProduct(string $productId): array;
}
