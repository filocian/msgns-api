<?php

declare(strict_types=1);

use App\Models\User;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
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
        ->assertStatus(401);
});

it('returns 403 for authenticated user without manage_subscription_types permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'stateful-api')
        ->getJson('/api/v2/billing/stripe/products/prod_abc/prices')
        ->assertStatus(403);
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
