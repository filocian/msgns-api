<?php

declare(strict_types=1);

use Src\Billing\Application\Queries\ListStripeProductPrices\ListStripeProductPricesHandler;
use Src\Billing\Application\Queries\ListStripeProductPrices\ListStripeProductPricesQuery;
use Src\Billing\Application\Resources\StripePriceResource;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;
use Src\Billing\Domain\Errors\StripeProductUnavailable;
use Src\Billing\Domain\Ports\StripeCatalogPort;

/**
 * @param list<StripeCatalogPrice> $prices
 */
function makePricesCatalog(array $prices): StripeCatalogPort
{
    return new class($prices) implements StripeCatalogPort {
        public int $listPricesCalls = 0;

        public ?string $listPricesCalledWith = null;

        /** @param list<StripeCatalogPrice> $prices */
        public function __construct(private readonly array $prices) {}

        public function listProducts(): array
        {
            return [];
        }

        public function getProduct(string $productId): StripeCatalogProduct
        {
            throw StripeProductUnavailable::withProductId($productId);
        }

        public function listPricesForProduct(string $productId): array
        {
            $this->listPricesCalls++;
            $this->listPricesCalledWith = $productId;
            return $this->prices;
        }
    };
}

describe('ListStripeProductPricesHandler', function () {
    it('returns a list of StripePriceResource mapped from the port', function () {
        $prices = [
            new StripeCatalogPrice(
                id: 'price_monthly',
                productId: 'prod_abc',
                currency: 'eur',
                unitAmount: 999,
                type: 'recurring',
                interval: 'month',
                active: true,
            ),
            new StripeCatalogPrice(
                id: 'price_annual',
                productId: 'prod_abc',
                currency: 'eur',
                unitAmount: 9990,
                type: 'recurring',
                interval: 'year',
                active: true,
            ),
        ];
        $catalog = makePricesCatalog($prices);

        $handler = new ListStripeProductPricesHandler($catalog);

        $result = $handler->handle(new ListStripeProductPricesQuery('prod_abc'));

        expect($result)->toBeArray()->toHaveCount(2);
        expect($result[0])->toBeInstanceOf(StripePriceResource::class);
        expect($result[0]->id)->toBe('price_monthly');
        expect($result[1]->interval)->toBe('year');
        expect($catalog->listPricesCalledWith)->toBe('prod_abc');
    });

    it('returns an empty list when the product has no prices', function () {
        $catalog = makePricesCatalog([]);
        $handler = new ListStripeProductPricesHandler($catalog);

        $result = $handler->handle(new ListStripeProductPricesQuery('prod_empty'));

        expect($result)->toBeArray()->toBeEmpty();
    });

    it('filters out inactive prices', function () {
        $prices = [
            new StripeCatalogPrice(
                id: 'price_active',
                productId: 'prod_abc',
                currency: 'eur',
                unitAmount: 999,
                type: 'recurring',
                interval: 'month',
                active: true,
            ),
            new StripeCatalogPrice(
                id: 'price_inactive',
                productId: 'prod_abc',
                currency: 'eur',
                unitAmount: 500,
                type: 'recurring',
                interval: 'month',
                active: false,
            ),
        ];
        $catalog = makePricesCatalog($prices);
        $handler = new ListStripeProductPricesHandler($catalog);

        $result = $handler->handle(new ListStripeProductPricesQuery('prod_abc'));

        expect($result)->toHaveCount(1);
        expect($result[0]->id)->toBe('price_active');
    });
});
