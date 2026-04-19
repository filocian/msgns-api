<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;
use Src\Billing\Domain\Errors\StripeProductUnavailable;
use Src\Billing\Infrastructure\Services\StripeCatalogService;
use Src\Shared\Core\Ports\CachePort;
use Stripe\Collection;
use Stripe\Exception\InvalidRequestException;
use Stripe\Price;
use Stripe\Product;
use Stripe\Service\PriceService;
use Stripe\Service\ProductService;
use Stripe\StripeClient;

function makeStripeProduct(string $id, bool $active = true, string $name = 'Test Plan'): Product
{
    return Product::constructFrom([
        'id'       => $id,
        'name'     => $name,
        'active'   => $active,
        'metadata' => [],
    ]);
}

function makeStripePrice(
    string $id,
    string $productId,
    int $unitAmount = 1000,
    string $currency = 'eur',
    string $type = 'recurring',
    ?string $interval = 'month',
    bool $active = true,
): Price {
    $data = [
        'id'          => $id,
        'product'     => $productId,
        'unit_amount' => $unitAmount,
        'currency'    => $currency,
        'type'        => $type,
        'active'      => $active,
        'recurring'   => $type === 'recurring' ? ['interval' => $interval] : null,
    ];

    return Price::constructFrom($data);
}

function makeStripeCollection(array $data): Collection
{
    return Collection::constructFrom([
        'data'     => $data,
        'has_more' => false,
    ]);
}

beforeEach(function () {
    $this->stripe = Mockery::mock(StripeClient::class);
    $this->stripe->products = Mockery::mock(ProductService::class);
    $this->stripe->prices = Mockery::mock(PriceService::class);

    $this->cache = Mockery::mock(CachePort::class);
});

// ─── listProducts() ──────────────────────────────────────────────────────────

it('caches listProducts() under billing:stripe:products:v1 with TTL 300', function () {
    $this->cache->shouldReceive('remember')
        ->once()
        ->with('billing:stripe:products:v1', 300, Mockery::type('Closure'))
        ->andReturnUsing(fn ($key, $ttl, $callback) => $callback());

    $this->stripe->products->shouldReceive('all')
        ->once()
        ->andReturn(makeStripeCollection([
            makeStripeProduct('prod_active_1'),
        ]));

    $this->stripe->prices->shouldReceive('all')
        ->once()
        ->with(['product' => 'prod_active_1', 'active' => true, 'limit' => 100])
        ->andReturn(makeStripeCollection([
            makeStripePrice('price_1', 'prod_active_1'),
        ]));

    $service = new StripeCatalogService($this->stripe, $this->cache);
    $result = $service->listProducts();

    expect($result)->toBeArray()->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(StripeCatalogProduct::class)
        ->and($result[0]->id)->toBe('prod_active_1');
});

it('returns cached value on second call without hitting Stripe', function () {
    $cached = [
        new StripeCatalogProduct('prod_cached', 'Cached', true, [], []),
    ];

    // First call triggers callback, second returns cached.
    $callCount = 0;
    $this->cache->shouldReceive('remember')
        ->twice()
        ->with('billing:stripe:products:v1', 300, Mockery::type('Closure'))
        ->andReturnUsing(function ($key, $ttl, $callback) use (&$callCount, $cached) {
            $callCount++;
            if ($callCount === 1) {
                return $callback();
            }

            return $cached;
        });

    $this->stripe->products->shouldReceive('all')
        ->once() // Only called on first cache-miss
        ->andReturn(makeStripeCollection([makeStripeProduct('prod_active_1')]));

    $this->stripe->prices->shouldReceive('all')
        ->once()
        ->andReturn(makeStripeCollection([makeStripePrice('price_1', 'prod_active_1')]));

    $service = new StripeCatalogService($this->stripe, $this->cache);
    $service->listProducts();
    $result2 = $service->listProducts();

    expect($result2)->toBe($cached);
});

// ─── getProduct() ────────────────────────────────────────────────────────────

it('returns StripeCatalogProduct when product is active', function () {
    $this->stripe->products->shouldReceive('retrieve')
        ->once()
        ->with('prod_ok', Mockery::any())
        ->andReturn(makeStripeProduct('prod_ok', active: true, name: 'OK Plan'));

    $this->stripe->prices->shouldReceive('all')
        ->once()
        ->with(['product' => 'prod_ok', 'active' => true, 'limit' => 100])
        ->andReturn(makeStripeCollection([
            makeStripePrice('price_monthly', 'prod_ok'),
        ]));

    $service = new StripeCatalogService($this->stripe, $this->cache);
    $product = $service->getProduct('prod_ok');

    expect($product->id)->toBe('prod_ok')
        ->and($product->name)->toBe('OK Plan')
        ->and($product->active)->toBeTrue()
        ->and($product->prices)->toHaveCount(1)
        ->and($product->prices[0])->toBeInstanceOf(StripeCatalogPrice::class);
});

it('throws StripeProductUnavailable when Stripe returns InvalidRequestException', function () {
    $this->stripe->products->shouldReceive('retrieve')
        ->once()
        ->with('prod_missing', Mockery::any())
        ->andThrow(new InvalidRequestException('No such product'));

    $service = new StripeCatalogService($this->stripe, $this->cache);

    expect(fn () => $service->getProduct('prod_missing'))
        ->toThrow(StripeProductUnavailable::class);
});

it('throws StripeProductUnavailable when product is inactive', function () {
    $this->stripe->products->shouldReceive('retrieve')
        ->once()
        ->with('prod_inactive', Mockery::any())
        ->andReturn(makeStripeProduct('prod_inactive', active: false));

    $service = new StripeCatalogService($this->stripe, $this->cache);

    expect(fn () => $service->getProduct('prod_inactive'))
        ->toThrow(StripeProductUnavailable::class);
});

// ─── listPricesForProduct() ──────────────────────────────────────────────────

it('returns list of StripeCatalogPrice without caching', function () {
    // No cache->remember expected — this endpoint is NOT cached.
    $this->cache->shouldNotReceive('remember');

    $this->stripe->prices->shouldReceive('all')
        ->once()
        ->with(['product' => 'prod_x', 'active' => true, 'limit' => 100])
        ->andReturn(makeStripeCollection([
            makeStripePrice('price_a', 'prod_x'),
            makeStripePrice('price_b', 'prod_x', type: 'one_time', interval: null),
        ]));

    $service = new StripeCatalogService($this->stripe, $this->cache);
    $prices = $service->listPricesForProduct('prod_x');

    expect($prices)->toHaveCount(2)
        ->and($prices[0])->toBeInstanceOf(StripeCatalogPrice::class)
        ->and($prices[1]->type)->toBe('one_time')
        ->and($prices[1]->interval)->toBeNull();
});
