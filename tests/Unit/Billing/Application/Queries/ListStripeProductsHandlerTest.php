<?php

declare(strict_types=1);

use Src\Billing\Application\Queries\ListStripeProducts\ListStripeProductsHandler;
use Src\Billing\Application\Queries\ListStripeProducts\ListStripeProductsQuery;
use Src\Billing\Application\Queries\ListStripeProducts\StripeProductResource;
use Src\Billing\Application\Resources\StripePriceResource;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;
use Src\Billing\Domain\Errors\StripeProductUnavailable;
use Src\Billing\Domain\Ports\StripeCatalogPort;

/**
 * @param list<StripeCatalogProduct> $products
 */
function makeListCatalog(array $products): StripeCatalogPort
{
    return new class($products) implements StripeCatalogPort {
        /** @param list<StripeCatalogProduct> $products */
        public function __construct(private readonly array $products) {}

        public function listProducts(): array
        {
            return $this->products;
        }

        public function getProduct(string $productId): StripeCatalogProduct
        {
            throw StripeProductUnavailable::withProductId($productId);
        }

        public function listPricesForProduct(string $productId): array
        {
            return [];
        }
    };
}

describe('ListStripeProductsHandler', function () {
    it('returns a list of StripeProductResource mapped from the port', function () {
        $price = new StripeCatalogPrice(
            id: 'price_monthly',
            productId: 'prod_abc',
            currency: 'eur',
            unitAmount: 999,
            type: 'recurring',
            interval: 'month',
            active: true,
        );
        $product = new StripeCatalogProduct(
            id: 'prod_abc',
            name: 'Pro',
            active: true,
            prices: [$price],
            metadata: ['tier' => 'premium'],
        );

        $catalog = makeListCatalog([$product]);
        $handler = new ListStripeProductsHandler($catalog);

        $result = $handler->handle(new ListStripeProductsQuery());

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0])->toBeInstanceOf(StripeProductResource::class);
        expect($result[0]->id)->toBe('prod_abc');
        expect($result[0]->name)->toBe('Pro');
        expect($result[0]->active)->toBeTrue();
        expect($result[0]->prices)->toHaveCount(1);
        expect($result[0]->prices[0])->toBeInstanceOf(StripePriceResource::class);
        expect($result[0]->prices[0]->id)->toBe('price_monthly');
        expect($result[0]->prices[0]->currency)->toBe('eur');
    });

    it('returns an empty list when the port returns no products', function () {
        $catalog = makeListCatalog([]);
        $handler = new ListStripeProductsHandler($catalog);

        $result = $handler->handle(new ListStripeProductsQuery());

        expect($result)->toBeArray()->toBeEmpty();
    });
});
