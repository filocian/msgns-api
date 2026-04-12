<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Permissions\DomainRoles;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

beforeEach(function () {
    Permission::findOrCreate(DomainPermissions::MANAGE_SUBSCRIPTION_TYPES, DomainRoles::GUARD);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(DomainPermissions::MANAGE_SUBSCRIPTION_TYPES);
});

// ─── LIST ────────────────────────────────────────────────────────────────────

it('returns 200 with paginated subscription types for authorized user', function () {
    SubscriptionTypeModel::factory()->count(3)->create();

    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/subscriptions/admin/subscription-types')
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
});

it('filters by mode=classic', function () {
    SubscriptionTypeModel::factory()->create(['mode' => 'classic']);
    SubscriptionTypeModel::factory()->create(['mode' => 'prepaid']);

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/subscriptions/admin/subscription-types?mode=classic')
        ->assertStatus(200);

    expect($response->json('meta.total'))->toBe(1);
});

it('filters by is_active=0 to show inactive types', function () {
    SubscriptionTypeModel::factory()->create(['is_active' => true]);
    SubscriptionTypeModel::factory()->create(['is_active' => false]);

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/subscriptions/admin/subscription-types?is_active=0')
        ->assertStatus(200);

    expect($response->json('meta.total'))->toBe(1);
});

it('returns 403 for user without manage_subscription_types permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'stateful-api')
        ->getJson('/api/v2/subscriptions/admin/subscription-types')
        ->assertStatus(403);
});

it('returns 401 for unauthenticated request on list', function () {
    $this->getJson('/api/v2/subscriptions/admin/subscription-types')
        ->assertStatus(401);
});

// ─── CREATE ──────────────────────────────────────────────────────────────────

it('creates a classic subscription type and returns 201 with auto-generated slug', function () {
    $response = $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Google Review Basic',
            'description'             => 'Basic plan',
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly', 'annual'],
            'base_price_cents'        => 200,
            'permission_name'         => 'ai.google-review-basic',
            'google_review_limit'     => 50,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(201);

    expect($response->json('data.slug'))->toBe('google-review-basic')
        ->and($response->json('data.name'))->toBe('Google Review Basic');
});

it('creates a prepaid subscription type with null billing_periods', function () {
    $response = $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Google Review Prepaid',
            'mode'                    => 'prepaid',
            'billing_periods'         => null,
            'base_price_cents'        => 400,
            'permission_name'         => 'ai.google-review-prepaid',
            'google_review_limit'     => 50,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(201);

    expect($response->json('data.billingPeriods'))->toBeNull();
});

it('auto-creates Spatie permission on creation', function () {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'New Plan',
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.new-plan',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(201);

    expect(Permission::where('name', 'ai.new-plan')->where('guard_name', 'stateful-api')->exists())->toBeTrue();
});

it('returns 400 when name is missing', function () {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.test',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(400);
});

it('returns 400 when mode is invalid', function () {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Bad Mode Plan',
            'mode'                    => 'invalid',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.bad-mode',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(400);
});

it('returns 400 when billing_periods is missing for classic mode', function () {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Classic No Periods',
            'mode'                    => 'classic',
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.classic-no-periods',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(400);
});

it('returns 400 when billing_periods contains invalid values', function () {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Bad Period Plan',
            'mode'                    => 'classic',
            'billing_periods'         => ['weekly'],
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.bad-period',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(400);
});

it('returns 400 when billing_periods is provided for prepaid mode', function () {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Prepaid With Periods',
            'mode'                    => 'prepaid',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.prepaid-periods',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(400);
});

it('returns 400 when permission_name is duplicate', function () {
    SubscriptionTypeModel::factory()->create(['permission_name' => 'ai.duplicate']);

    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Another Plan',
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.duplicate',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(400);
});

it('returns 400 when name is duplicate (slug collision)', function () {
    SubscriptionTypeModel::factory()->create(['name' => 'Duplicate Plan']);

    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Duplicate Plan',
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.duplicate-plan',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(400);
});

it('returns 400 when base_price_cents is negative', function () {
    $this->actingAs($this->admin, 'stateful-api')
        ->postJson('/api/v2/subscriptions/admin/subscription-types', [
            'name'                    => 'Negative Price Plan',
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => -1,
            'permission_name'         => 'ai.negative-price',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(400);
});

it('returns 401 for unauthenticated create request', function () {
    $this->postJson('/api/v2/subscriptions/admin/subscription-types', [])
        ->assertStatus(401);
});

// ─── SHOW ────────────────────────────────────────────────────────────────────

it('returns 200 with full subscription type data', function () {
    $model = SubscriptionTypeModel::factory()->create();

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->getJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}")
        ->assertStatus(200);

    expect($response->json('data.id'))->toBe($model->id);
});

it('returns 404 for non-existent id on show', function () {
    $this->actingAs($this->admin, 'stateful-api')
        ->getJson('/api/v2/subscriptions/admin/subscription-types/99999')
        ->assertStatus(404);
});

// ─── UPDATE ──────────────────────────────────────────────────────────────────

it('updates all fields and re-generates slug', function () {
    $model = SubscriptionTypeModel::factory()->create(['name' => 'Old Name', 'mode' => 'classic']);

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => 'New Name',
            'description'             => null,
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => 300,
            'permission_name'         => 'ai.new-name',
            'google_review_limit'     => 20,
            'instagram_content_limit' => 5,
        ])
        ->assertStatus(200);

    expect($response->json('data.name'))->toBe('New Name')
        ->and($response->json('data.slug'))->toBe('new-name');
});

it('updates permission_name and auto-creates new Spatie permission', function () {
    $model = SubscriptionTypeModel::factory()->create(['mode' => 'classic']);

    $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => $model->name,
            'description'             => null,
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.brand-new-permission',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(200);

    expect(Permission::where('name', 'ai.brand-new-permission')->where('guard_name', 'stateful-api')->exists())->toBeTrue();
});

it('allows same permission_name on update (excludes self from unique check)', function () {
    $model = SubscriptionTypeModel::factory()->create(['mode' => 'classic', 'permission_name' => 'ai.self-perm']);

    $this->actingAs($this->admin, 'stateful-api')
        ->putJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}", [
            'name'                    => $model->name,
            'description'             => null,
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.self-perm',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(200);
});

it('returns 404 for non-existent id on update', function () {
    $this->actingAs($this->admin, 'stateful-api')
        ->putJson('/api/v2/subscriptions/admin/subscription-types/99999', [
            'name'                    => 'Ghost Plan',
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly'],
            'base_price_cents'        => 100,
            'permission_name'         => 'ai.ghost',
            'google_review_limit'     => 10,
            'instagram_content_limit' => 0,
        ])
        ->assertStatus(404);
});

// ─── TOGGLE ACTIVE ───────────────────────────────────────────────────────────

it('toggles is_active from true to false', function () {
    $model = SubscriptionTypeModel::factory()->create(['is_active' => true]);

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->patchJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}/toggle-active")
        ->assertStatus(200);

    expect($response->json('data.isActive'))->toBeFalse();
});

it('toggles is_active from false to true', function () {
    $model = SubscriptionTypeModel::factory()->create(['is_active' => false]);

    $response = $this->actingAs($this->admin, 'stateful-api')
        ->patchJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}/toggle-active")
        ->assertStatus(200);

    expect($response->json('data.isActive'))->toBeTrue();
});

// ─── DELETE ──────────────────────────────────────────────────────────────────

it('soft-deletes and returns 204', function () {
    $model = SubscriptionTypeModel::factory()->create();

    $this->actingAs($this->admin, 'stateful-api')
        ->deleteJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}")
        ->assertStatus(204);

    expect(SubscriptionTypeModel::withTrashed()->find($model->id)->deleted_at)->not->toBeNull();
});

it('returns 404 for non-existent id on delete', function () {
    $this->actingAs($this->admin, 'stateful-api')
        ->deleteJson('/api/v2/subscriptions/admin/subscription-types/99999')
        ->assertStatus(404);
});

it('returns 409 when active subscriptions exist', function () {
    $model = SubscriptionTypeModel::factory()->create();

    // Mock the repository to return true from hasActiveSubscriptions
    $mockRepo = Mockery::mock(\Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort::class);
    $mockRepo->shouldReceive('findById')->with($model->id)->andReturn($model->toDomainEntity());
    $mockRepo->shouldReceive('hasActiveSubscriptions')->with($model->id)->andReturn(true);
    app()->instance(\Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort::class, $mockRepo);

    $this->actingAs($this->admin, 'stateful-api')
        ->deleteJson("/api/v2/subscriptions/admin/subscription-types/{$model->id}")
        ->assertStatus(409);
});
