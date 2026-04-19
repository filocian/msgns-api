<?php

declare(strict_types=1);

use App\Models\User;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\Errors\StripeCatalogMisconfigured;
use Src\Billing\Domain\Errors\StripeCatalogUpstreamUnavailable;
use Src\Billing\Domain\Errors\StripeProductUnavailable;
use Src\Billing\Domain\Ports\StripeCatalogPort;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Permissions\DomainRoles;

beforeEach(function () {
    Permission::findOrCreate(DomainPermissions::MANAGE_SUBSCRIPTION_TYPES, DomainRoles::GUARD);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(DomainPermissions::MANAGE_SUBSCRIPTION_TYPES);
});

it('returns 401 for unauthenticated request on stripe product prices', function () {
    $this->getJson('/api/v2/billing/stripe/products/prod_abc/prices')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'auth.unauthenticated');
});

it('returns 403 for authenticated user without manage_subscription_types permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products/prod_abc/prices')
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'http.forbidden');
});

it('returns 404 canonical envelope when stripe product is unavailable', function () {
    $this->mock(StripeCatalogPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listPricesForProduct')
            ->once()
            ->with('prod_missing')
            ->andThrow(StripeProductUnavailable::withProductId('prod_missing'));
    });

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products/prod_missing/prices')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'billing.stripe_product.unavailable');
});

it('returns 500 canonical envelope when stripe prices endpoint is misconfigured', function () {
    $this->mock(StripeCatalogPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listPricesForProduct')
            ->once()
            ->with('prod_abc')
            ->andThrow(StripeCatalogMisconfigured::withoutContext());
    });

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products/prod_abc/prices')
        ->assertStatus(500)
        ->assertJsonPath('error.code', 'stripe_catalog.misconfigured');
});

it('returns 502 canonical envelope when stripe prices upstream is unavailable', function () {
    $this->mock(StripeCatalogPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listPricesForProduct')
            ->once()
            ->with('prod_abc')
            ->andThrow(StripeCatalogUpstreamUnavailable::withReason('stripe_api_error'));
    });

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products/prod_abc/prices')
        ->assertStatus(502)
        ->assertJsonPath('error.code', 'stripe_catalog.upstream_unavailable')
        ->assertJsonPath('error.context.reason', 'stripe_api_error');
});

it('returns 200 with list of StripePriceResource for admin', function () {
    $this->mock(StripeCatalogPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listPricesForProduct')
            ->once()
            ->with('prod_abc')
            ->andReturn([
                new StripeCatalogPrice(
                    id: 'price_m',
                    productId: 'prod_abc',
                    currency: 'eur',
                    unitAmount: 1000,
                    type: 'recurring',
                    interval: 'month',
                    active: true,
                ),
                new StripeCatalogPrice(
                    id: 'price_y',
                    productId: 'prod_abc',
                    currency: 'eur',
                    unitAmount: 10000,
                    type: 'recurring',
                    interval: 'year',
                    active: true,
                ),
            ]);
    });

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products/prod_abc/prices')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'productId', 'currency', 'unit_amount', 'type', 'interval', 'active'],
            ],
        ])
        ->assertJsonPath('data.0.id', 'price_m')
        ->assertJsonPath('data.0.interval', 'month')
        ->assertJsonPath('data.1.interval', 'year');
});

it('returns empty array when product has no prices', function () {
    $this->mock(StripeCatalogPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listPricesForProduct')
            ->once()
            ->with('prod_empty')
            ->andReturn([]);
    });

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products/prod_empty/prices')
        ->assertStatus(200)
        ->assertJson(['data' => []]);
});
