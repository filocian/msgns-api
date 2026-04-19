<?php

declare(strict_types=1);

use App\Models\User;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;
use Src\Billing\Domain\Errors\StripeCatalogMisconfigured;
use Src\Billing\Domain\Errors\StripeCatalogUpstreamUnavailable;
use Src\Billing\Domain\Ports\StripeCatalogPort;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Permissions\DomainRoles;

beforeEach(function () {
    Permission::findOrCreate(DomainPermissions::MANAGE_SUBSCRIPTION_TYPES, DomainRoles::GUARD);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(DomainPermissions::MANAGE_SUBSCRIPTION_TYPES);
});

it('returns 401 for unauthenticated request on stripe products list', function () {
    $this->getJson('/api/v2/billing/stripe/products')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'auth.unauthenticated');
});

it('returns 403 for authenticated user without manage_subscription_types permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products')
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'http.forbidden');
});

it('returns 500 canonical envelope when stripe catalog is misconfigured', function () {
    $this->mock(StripeCatalogPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listProducts')
            ->once()
            ->andThrow(StripeCatalogMisconfigured::withoutContext());
    });

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products')
        ->assertStatus(500)
        ->assertJsonPath('error.code', 'stripe_catalog.misconfigured')
        ->assertJsonPath('error.context', []);
});

it('returns 502 canonical envelope when stripe catalog upstream is unavailable', function () {
    $this->mock(StripeCatalogPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listProducts')
            ->once()
            ->andThrow(StripeCatalogUpstreamUnavailable::withReason('stripe_unreachable'));
    });

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products')
        ->assertStatus(502)
        ->assertJsonPath('error.code', 'stripe_catalog.upstream_unavailable')
        ->assertJsonPath('error.context.reason', 'stripe_unreachable');
});

it('returns 200 with list of StripeProductResource for admin', function () {
    $this->mock(StripeCatalogPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listProducts')
            ->once()
            ->andReturn([
                new StripeCatalogProduct(
                    id: 'prod_abc',
                    name: 'Pro Plan',
                    active: true,
                    prices: [
                        new StripeCatalogPrice(
                            id: 'price_m',
                            productId: 'prod_abc',
                            currency: 'eur',
                            unitAmount: 1000,
                            type: 'recurring',
                            interval: 'month',
                            active: true,
                        ),
                    ],
                    metadata: [],
                ),
            ]);
    });

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'active', 'prices', 'metadata'],
            ],
        ])
        ->assertJsonPath('data.0.id', 'prod_abc')
        ->assertJsonPath('data.0.prices.0.id', 'price_m')
        ->assertJsonPath('data.0.prices.0.currency', 'eur')
        ->assertJsonPath('data.0.prices.0.unit_amount', 1000);
});

it('returns an empty list when the port returns no products', function () {
    $this->mock(StripeCatalogPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listProducts')
            ->once()
            ->andReturn([]);
    });

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products')
        ->assertStatus(200)
        ->assertJson(['data' => []]);
});
