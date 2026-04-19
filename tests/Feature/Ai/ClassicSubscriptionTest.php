<?php

declare(strict_types=1);

use App\Models\User;
use Mockery\MockInterface;
use Src\Ai\Domain\Errors\SubscriptionRequires3DS;
use Src\Ai\Domain\Ports\ClassicSubscriptionBrokerPort;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeClassicSubscriptionType(): SubscriptionTypeModel
{
    $type = SubscriptionTypeModel::factory()->create([
        'mode'             => 'classic',
        'billing_periods'  => ['monthly', 'annual'],
        'stripe_price_ids' => ['monthly' => 'price_monthly_test', 'annual' => 'price_annual_test'],
        'permission_name'  => 'ai.test_plan',
        'is_active'        => true,
    ]);

    // Ensure Spatie permission exists in DB so givePermissionTo() works
    \Spatie\Permission\Models\Permission::findOrCreate('ai.test_plan', 'stateful-api');

    return $type;
}

function makeBrokerResult(int $userId): array
{
    return [
        'stripe_subscription_id' => 'sub_test_' . $userId,
        'current_period_start'   => now()->timestamp,
        'current_period_end'     => now()->addMonth()->timestamp,
    ];
}

// ─── POST /api/v2/ai/subscriptions/classic ───────────────────────────────────

describe('POST /api/v2/ai/subscriptions/classic', function (): void {

    it('creates subscription, grants permission, returns 201', function (): void {
        $user = $this->create_user(['email' => 'sub-create@test.com']);
        $type = makeClassicSubscriptionType();
        $result = makeBrokerResult($user->id);

        $this->mock(ClassicSubscriptionBrokerPort::class, function (MockInterface $mock) use ($user, $result): void {
            $mock->shouldReceive('createSubscription')
                ->once()
                ->with($user->id, 'price_monthly_test', 'pm_test_visa')
                ->andReturn($result);
        });

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/subscriptions/classic', [
                'subscription_type_id' => $type->id,
                'billing_period'       => 'monthly',
                'payment_method_id'    => 'pm_test_visa',
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'billing_period', 'status', 'current_period_start', 'current_period_end', 'subscription_type']]);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id'                => $user->id,
            'subscription_type_id'   => $type->id,
            'billing_period'         => 'monthly',
            'stripe_subscription_id' => $result['stripe_subscription_id'],
            'status'                 => 'active',
        ]);

        $user->refresh();
        expect($user->hasPermissionTo('ai.test_plan'))->toBeTrue();
    });

    it('returns 402 with client_secret when Stripe requires 3DS', function (): void {
        $user = $this->create_user(['email' => 'sub-3ds@test.com']);
        $type = makeClassicSubscriptionType();

        $this->mock(ClassicSubscriptionBrokerPort::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createSubscription')
                ->once()
                ->andThrow(SubscriptionRequires3DS::forPaymentIntent('pi_test_secret_xxx'));
        });

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/subscriptions/classic', [
                'subscription_type_id' => $type->id,
                'billing_period'       => 'monthly',
                'payment_method_id'    => 'pm_test_3ds',
            ])
            ->assertStatus(402)
            ->assertJsonPath('error.context.client_secret', 'pi_test_secret_xxx');
    });

    it('returns 409 when user already has an active subscription', function (): void {
        $user = $this->create_user(['email' => 'sub-duplicate@test.com']);
        $type = makeClassicSubscriptionType();

        UserSubscriptionModel::query()->create([
            'user_id'                => $user->id,
            'subscription_type_id'   => $type->id,
            'billing_period'         => 'monthly',
            'stripe_subscription_id' => 'sub_existing',
            'status'                 => 'active',
            'current_period_start'   => now(),
            'current_period_end'     => now()->addMonth(),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/subscriptions/classic', [
                'subscription_type_id' => $type->id,
                'billing_period'       => 'monthly',
                'payment_method_id'    => 'pm_test_visa',
            ])
            ->assertStatus(409);
    });

    it('returns 409 when user has a cancelled (still-active) subscription', function (): void {
        $user = $this->create_user(['email' => 'sub-cancelled-dup@test.com']);
        $type = makeClassicSubscriptionType();

        UserSubscriptionModel::query()->create([
            'user_id'                => $user->id,
            'subscription_type_id'   => $type->id,
            'billing_period'         => 'monthly',
            'stripe_subscription_id' => 'sub_cancelled',
            'status'                 => 'cancelled',
            'current_period_start'   => now()->subDays(10),
            'current_period_end'     => now()->addDays(20),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/subscriptions/classic', [
                'subscription_type_id' => $type->id,
                'billing_period'       => 'monthly',
                'payment_method_id'    => 'pm_test_visa',
            ])
            ->assertStatus(409);
    });

    it('returns 400 with validation errors when required fields are missing', function (): void {
        $user = $this->create_user(['email' => 'sub-validation@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/subscriptions/classic', [])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_failed');
    });

    it('returns 400 when billing_period is invalid', function (): void {
        $user = $this->create_user(['email' => 'sub-invalid-period@test.com']);
        $type = makeClassicSubscriptionType();

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/subscriptions/classic', [
                'subscription_type_id' => $type->id,
                'billing_period'       => 'weekly',
                'payment_method_id'    => 'pm_test',
            ])
            ->assertStatus(400)
            ->assertJsonPath('error.context.errors.billing_period.0', fn ($v) => str_contains($v, 'billing_period') || is_string($v));
    });

    it('returns 401 for unauthenticated request', function (): void {
        $this->postJson('/api/v2/ai/subscriptions/classic', [])
            ->assertStatus(401);
    });

});

// ─── DELETE /api/v2/ai/subscriptions/classic ─────────────────────────────────

describe('DELETE /api/v2/ai/subscriptions/classic', function (): void {

    it('cancels active subscription and sets status to cancelled without revoking permission', function (): void {
        $user = $this->create_user(['email' => 'sub-cancel@test.com']);
        $type = makeClassicSubscriptionType();
        $permission = $this->createPermission('ai.test_plan');
        $user->givePermissionTo($permission);

        UserSubscriptionModel::query()->create([
            'user_id'                => $user->id,
            'subscription_type_id'   => $type->id,
            'billing_period'         => 'monthly',
            'stripe_subscription_id' => 'sub_to_cancel',
            'status'                 => 'active',
            'current_period_start'   => now()->subDays(5),
            'current_period_end'     => now()->addDays(25),
        ]);

        $this->mock(ClassicSubscriptionBrokerPort::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('cancelSubscription')
                ->once()
                ->with($user->id);
        });

        $this->actingAs($user, 'stateful-api')
            ->deleteJson('/api/v2/ai/subscriptions/classic')
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'status'  => 'cancelled',
        ]);
        $this->assertNotNull(
            UserSubscriptionModel::query()->where('user_id', $user->id)->first()?->cancelled_at
        );

        // Permission NOT revoked — that's BE-7's job
        $user->refresh();
        expect($user->hasPermissionTo('ai.test_plan'))->toBeTrue();
    });

    it('returns 404 when no active subscription exists', function (): void {
        $user = $this->create_user(['email' => 'sub-cancel-404@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->deleteJson('/api/v2/ai/subscriptions/classic')
            ->assertStatus(404);
    });

    it('returns 401 for unauthenticated request', function (): void {
        $this->deleteJson('/api/v2/ai/subscriptions/classic')
            ->assertStatus(401);
    });

});

// ─── GET /api/v2/ai/subscriptions/classic ────────────────────────────────────

describe('GET /api/v2/ai/subscriptions/classic', function (): void {

    it('returns active subscription with subscription type data', function (): void {
        $user = $this->create_user(['email' => 'sub-show@test.com']);
        $type = makeClassicSubscriptionType();

        UserSubscriptionModel::query()->create([
            'user_id'                => $user->id,
            'subscription_type_id'   => $type->id,
            'billing_period'         => 'annual',
            'stripe_subscription_id' => 'sub_show_test',
            'status'                 => 'active',
            'current_period_start'   => now(),
            'current_period_end'     => now()->addYear(),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/subscriptions/classic')
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.billing_period', 'annual')
            ->assertJsonPath('data.subscription_type.id', $type->id);
    });

    it('returns cancelled subscription (access still valid until period end)', function (): void {
        $user = $this->create_user(['email' => 'sub-show-cancelled@test.com']);
        $type = makeClassicSubscriptionType();

        UserSubscriptionModel::query()->create([
            'user_id'                => $user->id,
            'subscription_type_id'   => $type->id,
            'billing_period'         => 'monthly',
            'stripe_subscription_id' => 'sub_cancelled_show',
            'status'                 => 'cancelled',
            'current_period_start'   => now()->subDays(5),
            'current_period_end'     => now()->addDays(25),
            'cancelled_at'           => now(),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/subscriptions/classic')
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    });

    it('returns 404 when user has no active or cancelled subscription', function (): void {
        $user = $this->create_user(['email' => 'sub-show-404@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/subscriptions/classic')
            ->assertStatus(404);
    });

    it('does not return expired subscriptions', function (): void {
        $user = $this->create_user(['email' => 'sub-show-expired@test.com']);
        $type = makeClassicSubscriptionType();

        UserSubscriptionModel::query()->create([
            'user_id'                => $user->id,
            'subscription_type_id'   => $type->id,
            'billing_period'         => 'monthly',
            'stripe_subscription_id' => 'sub_expired',
            'status'                 => 'expired',
            'current_period_start'   => now()->subMonths(2),
            'current_period_end'     => now()->subMonth(),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/subscriptions/classic')
            ->assertStatus(404);
    });

    it('returns 401 for unauthenticated request', function (): void {
        $this->getJson('/api/v2/ai/subscriptions/classic')
            ->assertStatus(401);
    });

});
