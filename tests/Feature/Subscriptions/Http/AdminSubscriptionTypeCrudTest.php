<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;
use Src\Billing\Domain\Ports\StripeCatalogPort;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Permissions\DomainRoles;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

beforeEach(function (): void {
    Permission::findOrCreate(DomainPermissions::MANAGE_SUBSCRIPTION_TYPES, DomainRoles::GUARD);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(DomainPermissions::MANAGE_SUBSCRIPTION_TYPES);
});

/**
 * Helper: build a recurring EUR Stripe product with monthly + annual prices.
 *
 * @return array{0: StripeCatalogProduct, 1: list<StripeCatalogPrice>}
 */
function buildRecurringEurProduct(string $productId): array
{
    $prices = [
        new StripeCatalogPrice(
            id: 'price_monthly_' . $productId,
            productId: $productId,
            currency: 'eur',
            unitAmount: 2000,
            type: 'recurring',
            interval: 'month',
            active: true,
        ),
        new StripeCatalogPrice(
            id: 'price_annual_' . $productId,
            productId: $productId,
            currency: 'eur',
            unitAmount: 20000,
            type: 'recurring',
            interval: 'year',
            active: true,
        ),
    ];

    return [
        new StripeCatalogProduct(
            id: $productId,
            name: 'Test Product ' . $productId,
            active: true,
            prices: $prices,
            metadata: [],
        ),
        $prices,
    ];
}

function mockStripeCatalogPortFor(string $productId, ?StripeCatalogProduct $product = null, ?array $prices = null): void
{
    if ($product === null || $prices === null) {
        [$product, $prices] = buildRecurringEurProduct($productId);
    }

    test()->mock(StripeCatalogPort::class, function ($mock) use ($productId, $product, $prices): void {
        $mock->shouldReceive('getProduct')->with($productId)->andReturn($product);
        $mock->shouldReceive('listPricesForProduct')->with($productId)->andReturn($prices);
        $mock->shouldReceive('listProducts')->andReturn([$product]);
    });
}

// ─── LIST ────────────────────────────────────────────────────────────────────

it('returns 200 with paginated subscription types for authorized user', function (): void {
    SubscriptionTypeModel::factory()->count(3)->create();

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/subscriptions/admin/subscription-types')
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
});

it('filters by mode=classic', function (): void {
    SubscriptionTypeModel::factory()->create(['mode' => 'classic']);
    SubscriptionTypeModel::factory()->create(['mode' => 'prepaid']);

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/subscriptions/admin/subscription-types?mode=classic')
        ->assertStatus(200);

    expect($response->json('meta.total'))->toBe(1);
});

it('filters by is_active=0 to show inactive types', function (): void {
    SubscriptionTypeModel::factory()->create(['is_active' => true]);
    SubscriptionTypeModel::factory()->create(['is_active' => false]);

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/subscriptions/admin/subscription-types?is_active=0')
        ->assertStatus(200);

    expect($response->json('meta.total'))->toBe(1);
});

it('returns 403 for user without manage_subscription_types permission', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'stateful-api')
        ->getJson('/api/v2/subscriptions/admin/subscription-types')
        ->assertStatus(403);
});

it('returns 401 for unauthenticated request on list', function (): void {
    $this->getJson('/api/v2/subscriptions/admin/subscription-types')
        ->assertStatus(401);
});

// ─── CREATE ──────────────────────────────────────────────────────────────────

it('creates a subscription type from a recurring EUR stripe product', function (): void {
    mockStripeCatalogPortFor('prod_basic001');

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Google Review Basic',
            'description'             => 'Basic plan',
            'permission_name'         => 'ai.google-review-basic',
            'google_review_limit'     => 50,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_basic001',
        ])
        ->assertStatus(201);

    expect($response->json('data.slug'))->toBe('google-review-basic')
        ->and($response->json('data.name'))->toBe('Google Review Basic')
        ->and($response->json('data.mode'))->toBe('classic')
        ->and($response->json('data.basePriceCents'))->toBe(2000)
        ->and($response->json('data.billingPeriods'))->toBe(['monthly', 'annual'])
        ->and($response->json('data.stripeProductId'))->toBe('prod_basic001')
        ->and($response->json('data.stripePriceIds'))->toBe([
            'monthly' => 'price_monthly_prod_basic001',
            'annual'  => 'price_annual_prod_basic001',
        ]);
});

it('auto-creates Spatie permission on creation', function (): void {
    mockStripeCatalogPortFor('prod_newplan001');

    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'New Plan',
            'permission_name'         => 'ai.new-plan',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_newplan001',
        ])
        ->assertStatus(201);

    expect(Permission::where('name', 'ai.new-plan')->where('guard_name', 'stateful-api')->exists())->toBeTrue();
});

it('returns 400 when name is missing', function (): void {
    mockStripeCatalogPortFor('prod_noname001');

    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'permission_name'         => 'ai.test',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_noname001',
        ])
        ->assertStatus(400);
});

it('returns 400 when stripe_product_id is missing', function (): void {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'No Stripe Plan',
            'permission_name'         => 'ai.no-stripe',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(400);
});

it('returns 400 when stripe_product_id does not match prod_ pattern', function (): void {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Bad Stripe Plan',
            'permission_name'         => 'ai.bad-stripe',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'not_a_prod_id',
        ])
        ->assertStatus(400);
});

it('returns 400 when mode field is present (prohibited)', function (): void {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Has Mode Plan',
            'permission_name'         => 'ai.has-mode',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_hasmode001',
            'mode'                    => 'classic',
        ])
        ->assertStatus(400);
});

it('returns 400 when base_price_cents field is present (prohibited)', function (): void {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Has BasePrice Plan',
            'permission_name'         => 'ai.has-base-price',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_hasbase001',
            'base_price_cents'        => 200,
        ])
        ->assertStatus(400);
});

it('returns 400 when billing_periods field is present (prohibited)', function (): void {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Has BillingPeriods Plan',
            'permission_name'         => 'ai.has-billing',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_hasbilling001',
            'billing_periods'         => ['monthly'],
        ])
        ->assertStatus(400);
});

it('returns 400 when permission_name is duplicate', function (): void {
    SubscriptionTypeModel::factory()->create(['permission_name' => 'ai.duplicate']);
    mockStripeCatalogPortFor('prod_dupperm001');

    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Another Plan',
            'permission_name'         => 'ai.duplicate',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_dupperm001',
        ])
        ->assertStatus(400);
});

it('returns 400 when name is duplicate (slug collision)', function (): void {
    SubscriptionTypeModel::factory()->create(['name' => 'Duplicate Plan']);
    mockStripeCatalogPortFor('prod_dupname001');

    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Duplicate Plan',
            'permission_name'         => 'ai.duplicate-plan',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_dupname001',
        ])
        ->assertStatus(400);
});

it('returns 401 for unauthenticated create request', function (): void {
    $this->postJson('/api/v2/subscriptions/admin/subscription-types', [])
        ->assertStatus(401);
});

// ─── SHOW ────────────────────────────────────────────────────────────────────

it('returns 200 with full subscription type data', function (): void {
    $model = SubscriptionTypeModel::factory()->create();

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->getJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}")
        ->assertStatus(200);

    expect($response->json('data.id'))->toBe($model->id);
});

it('returns 404 for non-existent id on show', function (): void {
    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/subscriptions/admin/subscription-types/99999')
        ->assertStatus(404);
});

// ─── UPDATE ──────────────────────────────────────────────────────────────────

it('updates mutable fields and re-generates slug without touching stripe binding', function (): void {
    $model = SubscriptionTypeModel::factory()->create([
        'name'              => 'Old Name',
        'mode'              => 'classic',
        'stripe_product_id' => 'prod_untouched001',
    ]);

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => 'New Name',
            'description'             => null,
            'permission_name'         => 'ai.new-name',
            'google_review_limit'     => 20,
            'instagram_content_limit' => 5,
        ])
        ->assertStatus(200);

    expect($response->json('data.name'))->toBe('New Name')
        ->and($response->json('data.slug'))->toBe('new-name')
        ->and($response->json('data.stripeProductId'))->toBe('prod_untouched001');
});

it('updates permission_name and auto-creates new Spatie permission', function (): void {
    $model = SubscriptionTypeModel::factory()->create(['mode' => 'classic']);

    $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => $model->name,
            'description'             => null,
            'permission_name'         => 'ai.brand-new-permission',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(200);

    expect(Permission::where('name', 'ai.brand-new-permission')->where('guard_name', 'stateful-api')->exists())->toBeTrue();
});

it('allows same permission_name on update (excludes self from unique check)', function (): void {
    $model = SubscriptionTypeModel::factory()->create(['mode' => 'classic', 'permission_name' => 'ai.self-perm']);

    $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => $model->name,
            'description'             => null,
            'permission_name'         => 'ai.self-perm',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(200);
});

it('returns 400 when update payload contains stripe_product_id (prohibited)', function (): void {
    $model = SubscriptionTypeModel::factory()->create(['mode' => 'classic']);

    $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => $model->name,
            'description'             => null,
            'permission_name'         => 'ai.attempt',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'stripe_product_id'       => 'prod_attempt001',
        ])
        ->assertStatus(400);
});

it('returns 400 when update payload contains mode (prohibited)', function (): void {
    $model = SubscriptionTypeModel::factory()->create(['mode' => 'classic']);

    $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => $model->name,
            'description'             => null,
            'permission_name'         => 'ai.attempt',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'mode'                    => 'classic',
        ])
        ->assertStatus(400);
});

it('returns 400 when update payload contains base_price_cents (prohibited)', function (): void {
    $model = SubscriptionTypeModel::factory()->create(['mode' => 'classic']);

    $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => $model->name,
            'description'             => null,
            'permission_name'         => 'ai.attempt',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'base_price_cents'        => 500,
        ])
        ->assertStatus(400);
});

it('returns 400 when update payload contains billing_periods (prohibited)', function (): void {
    $model = SubscriptionTypeModel::factory()->create(['mode' => 'classic']);

    $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => $model->name,
            'description'             => null,
            'permission_name'         => 'ai.attempt',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
            'billing_periods'         => ['monthly'],
        ])
        ->assertStatus(400);
});

it('returns 404 for non-existent id on update', function (): void {
    $this->actingAs($this->admin, 'stateful-api')
        ->putJson('/api/v2/subscriptions/admin/subscription-types/99999', [
            'name'                    => 'Ghost Plan',
            'permission_name'         => 'ai.ghost',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(404);
});

// ─── TOGGLE ACTIVE ───────────────────────────────────────────────────────────

it('toggles is_active from true to false', function (): void {
    $model = SubscriptionTypeModel::factory()->create(['is_active' => true]);

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->patchJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}/toggle-active")
        ->assertStatus(200);

    expect($response->json('data.isActive'))->toBeFalse();
});

it('toggles is_active from false to true', function (): void {
    $model = SubscriptionTypeModel::factory()->create(['is_active' => false]);

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->patchJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}/toggle-active")
        ->assertStatus(200);

    expect($response->json('data.isActive'))->toBeTrue();
});

// ─── DELETE ──────────────────────────────────────────────────────────────────

it('soft-deletes and returns 204', function (): void {
    $model = SubscriptionTypeModel::factory()->create();

    $this->actingAs($this->admin, 'stateful-api')
        ->deleteJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}")
        ->assertStatus(204);

    expect(SubscriptionTypeModel::withTrashed()->find($model->id)->deleted_at)->not->toBeNull();
});

it('returns 404 for non-existent id on delete', function (): void {
    $this->actingAs($this->admin, 'stateful-api')
        ->deleteJson('/api/v2/subscriptions/admin/subscription-types/99999')
        ->assertStatus(404);
});

it('returns 409 when active subscriptions exist', function (): void {
    $model = SubscriptionTypeModel::factory()->create();

    $mockRepo = Mockery::mock(\Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort::class);
    $mockRepo->shouldReceive('findById')->with($model->id)->andReturn($model->toDomainEntity());
    $mockRepo->shouldReceive('hasActiveSubscriptions')->with($model->id)->andReturn(true);
    app()->instance(\Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort::class, $mockRepo);

    $this->actingAs($this->admin, 'stateful-api')
        ->deleteJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}")
        ->assertStatus(409);
});
