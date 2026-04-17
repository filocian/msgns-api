<?php

declare(strict_types=1);

use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;
use Src\Billing\Domain\Ports\StripeCatalogPort;

describe('StripeCatalogPort contract', function () {
    it('listProducts returns a list of StripeCatalogProduct', function () {
        $fake = new class implements StripeCatalogPort {
            public function listProducts(): array
            {
                return [
                    new StripeCatalogProduct(
                        id: 'prod_abc',
                        name: 'Pro',
                        active: true,
                        prices: [],
                        metadata: [],
                    ),
                ];
            }

            public function getProduct(string $productId): StripeCatalogProduct
            {
                return new StripeCatalogProduct(
                    id: $productId,
                    name: 'Pro',
                    active: true,
                    prices: [],
                    metadata: [],
                );
            }

            public function listPricesForProduct(string $productId): array
            {
                return [];
            }
        };

        $products = $fake->listProducts();

        expect($products)->toBeArray()->toHaveCount(1);
        expect($products[0])->toBeInstanceOf(StripeCatalogProduct::class);
        expect($products[0]->id)->toBe('prod_abc');
    });

    it('listPricesForProduct returns a list of StripeCatalogPrice for the given product id', function () {
        $fake = new class implements StripeCatalogPort {
            public function listProducts(): array
            {
                return [];
            }

            public function getProduct(string $productId): StripeCatalogProduct
            {
                return new StripeCatalogProduct(
                    id: $productId,
                    name: 'Pro',
                    active: true,
                    prices: [],
                    metadata: [],
                );
            }

            public function listPricesForProduct(string $productId): array
            {
                return [
                    new StripeCatalogPrice(
                        id: 'price_monthly',
                        productId: $productId,
                        currency: 'eur',
                        unitAmount: 999,
                        type: 'recurring',
                        interval: 'month',
                        active: true,
                    ),
                ];
            }
        };

        $prices = $fake->listPricesForProduct('prod_abc');

        expect($prices)->toBeArray()->toHaveCount(1);
        expect($prices[0])->toBeInstanceOf(StripeCatalogPrice::class);
        expect($prices[0]->productId)->toBe('prod_abc');
        expect($prices[0]->currency)->toBe('eur');
        expect($prices[0]->type)->toBe('recurring');
        expect($prices[0]->interval)->toBe('month');
    });
});
