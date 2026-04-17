<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Services;

use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;
use Src\Billing\Domain\Errors\StripeProductUnavailable;
use Src\Billing\Domain\Ports\StripeCatalogPort;
use Src\Shared\Core\Ports\CachePort;
use Stripe\Exception\InvalidRequestException;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;

final class StripeCatalogService implements StripeCatalogPort
{
    private const string CACHE_KEY_PRODUCTS = 'billing:stripe:products:v1';
    private const int CACHE_TTL = 300;

    public function __construct(
        private readonly StripeClient $stripe,
        private readonly CachePort $cache,
    ) {}

    /**
     * @return list<StripeCatalogProduct>
     */
    public function listProducts(): array
    {
        /** @var list<StripeCatalogProduct> $result */
        $result = $this->cache->remember(
            self::CACHE_KEY_PRODUCTS,
            self::CACHE_TTL,
            fn (): array => $this->fetchActiveProducts(),
        );

        return $result;
    }

    public function getProduct(string $productId): StripeCatalogProduct
    {
        try {
            $product = $this->stripe->products->retrieve($productId, []);
        } catch (InvalidRequestException) {
            throw StripeProductUnavailable::withProductId($productId);
        }

        if ($product->active !== true) {
            throw StripeProductUnavailable::withProductId($productId);
        }

        return $this->toProductDto($product);
    }

    /**
     * @return list<StripeCatalogPrice>
     */
    public function listPricesForProduct(string $productId): array
    {
        $collection = $this->stripe->prices->all([
            'product' => $productId,
            'active'  => true,
            'limit'   => 100,
        ]);

        $prices = [];
        foreach ($collection->data as $price) {
            $prices[] = $this->toPriceDto($price);
        }

        return $prices;
    }

    /**
     * @return list<StripeCatalogProduct>
     */
    private function fetchActiveProducts(): array
    {
        $collection = $this->stripe->products->all([
            'active' => true,
            'limit'  => 100,
        ]);

        $products = [];
        foreach ($collection->data as $product) {
            if ($product->active !== true) {
                continue;
            }
            $products[] = $this->toProductDto($product);
        }

        return $products;
    }

    private function toProductDto(Product $product): StripeCatalogProduct
    {
        $prices = $this->listPricesForProduct((string) $product->id);

        $metadata = [];
        /** @var mixed $value */
        foreach ($product->metadata->toArray() as $key => $value) {
            $metadata[(string) $key] = (string) $value;
        }

        return new StripeCatalogProduct(
            id: (string) $product->id,
            name: (string) $product->name,
            active: (bool) $product->active,
            prices: $prices,
            metadata: $metadata,
        );
    }

    private function toPriceDto(Price $price): StripeCatalogPrice
    {
        $interval = null;
        $type = (string) $price->type;
        if ($type === 'recurring' && $price->recurring !== null) {
            $interval = isset($price->recurring->interval) ? (string) $price->recurring->interval : null;
        }

        return new StripeCatalogPrice(
            id: (string) $price->id,
            productId: (string) $price->product,
            currency: (string) $price->currency,
            unitAmount: (int) $price->unit_amount,
            type: $type,
            interval: $interval,
            active: (bool) $price->active,
        );
    }
}
