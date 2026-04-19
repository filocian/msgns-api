<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;
use Src\Billing\Domain\Errors\StripeProductUnavailable;
use Src\Billing\Domain\Ports\StripeCatalogPort;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Permissions\DomainRoles;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

beforeEach(function (): void {
    Permission::findOrCreate(DomainPermissions::MANAGE_SUBSCRIPTION_TYPES, DomainRoles::GUARD);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(DomainPermissions::MANAGE_SUBSCRIPTION_TYPES);
});

function eurRecurringProduct(string $productId, array $intervals = ['month', 'year']): array
{
    $prices = [];
    foreach ($intervals as $interval) {
        $prices[] = new StripeCatalogPrice(
            id: 'price_' . $interval . '_' . $productId,
            productId: $productId,
            currency: 'eur',
            unitAmount: $interval === 'month' ? 2500 : 25000,
            type: 'recurring',
            interval: $interval,
            active: true,
        );
    }

    return [
        new StripeCatalogProduct(
            id: $productId,
            name: 'Product ' . $productId,
            active: true,
            prices: $prices,
            metadata: [],
        ),
        $prices,
    ];
}

// ─── HAPPY PATH ──────────────────────────────────────────────────────────────

it('creates and persists a SubscriptionType with all derived fields from a Stripe product', function (): void {
    [$product, $prices] = eurRecurringProduct('prod_happy001');
    $this->mock(StripeCatalogPort::class, function ($mock) use ($product, $prices): void {
        $mock->shouldReceive('getProduct')->with('prod_happy001')->andReturn($product);
        $mock->shouldReceive('listPricesForProduct')->with('prod_happy001')->andReturn($prices);
    });

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Happy Plan',
            'description'             => 'Happy plan description',
            'permission_name'         => 'ai.happy-plan',
            'google_review_limit'     => 100,
            'instagram_content_limit' => 50,
            'stripe_product_id'       => 'prod_happy001',
        ])
        ->assertStatus(201);

    expect($response->json('data.name'))->toBe('Happy Plan')
        ->and($response->json('data.slug'))->toBe('happy-plan')
        ->and($response->json('data.mode'))->toBe('classic')
        ->and($response->json('data.basePriceCents'))->toBe(2500)
        ->and($response->json('data.billingPeriods'))->toBe(['monthly', 'annual'])
        ->and($response->json('data.stripeProductId'))->toBe('prod_happy001')
        ->and($response->json('data.stripePriceIds'))->toBe([
            'monthly' => 'price_month_prod_happy001',
            'annual'  => 'price_year_prod_happy001',
        ]);

    $persisted = SubscriptionTypeModel::where('stripe_product_id', 'prod_happy001')->firstOrFail();
    expect($persisted->base_price_cents)->toBe(2500)
        ->and($persisted->mode)->toBe('classic')
        ->and($persisted->stripe_price_ids)->toBe([
            'monthly' => 'price_month_prod_happy001',
            'annual'  => 'price_year_prod_happy001',
        ]);
});

// ─── REJECTIONS ──────────────────────────────────────────────────────────────

it('returns 422 subscription_types.stripe_product.invalid_currency when price is not EUR', function (): void {
    $usd = new StripeCatalogPrice(
        id: 'price_usd_mo',
        productId: 'prod_usd001',
        currency: 'usd',
        unitAmount: 2000,
        type: 'recurring',
        interval: 'month',
        active: true,
    );
    $product = new StripeCatalogProduct(
        id: 'prod_usd001',
        name: 'USD Plan',
        active: true,
        prices: [$usd],
        metadata: [],
    );
    $this->mock(StripeCatalogPort::class, function ($mock) use ($product, $usd): void {
        $mock->shouldReceive('getProduct')->with('prod_usd001')->andReturn($product);
        $mock->shouldReceive('listPricesForProduct')->with('prod_usd001')->andReturn([$usd]);
    });

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'USD Plan',
            'permission_name'         => 'ai.usd',
            'google_review_limit'     => 1,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_usd001',
        ])
        ->assertStatus(422);

    expect($response->json('error.code'))->toBe('subscription_types.stripe_product.invalid_currency');
});

it('returns 422 subscription_types.stripe_product_id.duplicate on duplicate binding', function (): void {
    SubscriptionTypeModel::factory()->create(['stripe_product_id' => 'prod_dup001']);
    [$product, $prices] = eurRecurringProduct('prod_dup001');
    $this->mock(StripeCatalogPort::class, function ($mock) use ($product, $prices): void {
        $mock->shouldReceive('getProduct')->with('prod_dup001')->andReturn($product)->byDefault();
        $mock->shouldReceive('listPricesForProduct')->with('prod_dup001')->andReturn($prices)->byDefault();
    });

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Dup Plan',
            'permission_name'         => 'ai.dup',
            'google_review_limit'     => 1,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_dup001',
        ])
        ->assertStatus(422);

    expect($response->json('error.code'))->toBe('subscription_types.stripe_product_id.duplicate');
});

it('returns 422 subscription_types.stripe_product.mixed_prices when a product has recurring + one_time prices', function (): void {
    $recurring = new StripeCatalogPrice(
        id: 'price_rec',
        productId: 'prod_mixed001',
        currency: 'eur',
        unitAmount: 2000,
        type: 'recurring',
        interval: 'month',
        active: true,
    );
    $oneTime = new StripeCatalogPrice(
        id: 'price_one',
        productId: 'prod_mixed001',
        currency: 'eur',
        unitAmount: 5000,
        type: 'one_time',
        interval: null,
        active: true,
    );
    $product = new StripeCatalogProduct(
        id: 'prod_mixed001',
        name: 'Mixed',
        active: true,
        prices: [$recurring, $oneTime],
        metadata: [],
    );
    $this->mock(StripeCatalogPort::class, function ($mock) use ($product, $recurring, $oneTime): void {
        $mock->shouldReceive('getProduct')->with('prod_mixed001')->andReturn($product);
        $mock->shouldReceive('listPricesForProduct')->with('prod_mixed001')->andReturn([$recurring, $oneTime]);
    });

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Mixed Plan',
            'permission_name'         => 'ai.mixed',
            'google_review_limit'     => 1,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_mixed001',
        ])
        ->assertStatus(422);

    expect($response->json('error.code'))->toBe('subscription_types.stripe_product.mixed_prices');
});

it('returns 422 subscription_types.stripe_product.no_monthly_price when recurring product lacks monthly', function (): void {
    $annual = new StripeCatalogPrice(
        id: 'price_annual_only',
        productId: 'prod_nomonth001',
        currency: 'eur',
        unitAmount: 20000,
        type: 'recurring',
        interval: 'year',
        active: true,
    );
    $product = new StripeCatalogProduct(
        id: 'prod_nomonth001',
        name: 'Annual Only',
        active: true,
        prices: [$annual],
        metadata: [],
    );
    $this->mock(StripeCatalogPort::class, function ($mock) use ($product, $annual): void {
        $mock->shouldReceive('getProduct')->with('prod_nomonth001')->andReturn($product);
        $mock->shouldReceive('listPricesForProduct')->with('prod_nomonth001')->andReturn([$annual]);
    });

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'No Month',
            'permission_name'         => 'ai.nomonth',
            'google_review_limit'     => 1,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_nomonth001',
        ])
        ->assertStatus(422);

    expect($response->json('error.code'))->toBe('subscription_types.stripe_product.no_monthly_price');
});

it('returns 422 subscription_types.stripe_product.not_found when port throws StripeProductUnavailable', function (): void {
    $this->mock(StripeCatalogPort::class, function ($mock): void {
        $mock->shouldReceive('getProduct')->with('prod_unknown001')
            ->andThrow(StripeProductUnavailable::withProductId('prod_unknown001'));
    });

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Unknown',
            'permission_name'         => 'ai.unknown',
            'google_review_limit'     => 1,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_unknown001',
        ])
        ->assertStatus(422);

    expect($response->json('error.code'))->toBe('subscription_types.stripe_product.not_found');
});

// ─── UPDATE REJECTIONS ───────────────────────────────────────────────────────

it('PUT with stripe_product_id in payload returns 400', function (): void {
    $model = SubscriptionTypeModel::factory()->create(['mode' => 'classic']);

    $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => $model->name,
            'description'             => null,
            'permission_name'         => 'ai.edit-attempt',
            'google_review_limit'     => 1,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_bad001',
        ])
        ->assertStatus(400);
});

it('PUT with removed field base_price_cents returns 400', function (): void {
    $model = SubscriptionTypeModel::factory()->create(['mode' => 'classic']);

    $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => $model->name,
            'description'             => null,
            'permission_name'         => 'ai.edit-attempt',
            'google_review_limit'     => 1,
            'instagram_content_limit' => 0,
            'base_price_cents'        => 100,
        ])
        ->assertStatus(400);
});
