<?php

declare(strict_types=1);

use App\Models\User;
use Mockery\MockInterface;
use Src\Billing\Domain\Ports\BillingPort;

beforeEach(function () {
    $this->user = User::factory()->create();
});

// ─── LIST PAYMENT METHODS ─────────────────────────────────────────────────────

it('returns an empty array when user has no payment methods', function () {
    $this->mock(BillingPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listPaymentMethods')
            ->once()
            ->with($this->user->id)
            ->andReturn([]);
    });

    $this->actingAs($this->user, 'stateful-api')
        ->getJson('/api/v2/billing/me/payment-methods')
        ->assertStatus(200)
        ->assertJson(['data' => []]);
});

it('returns payment methods with correct shape', function () {
    $this->mock(BillingPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listPaymentMethods')
            ->once()
            ->with($this->user->id)
            ->andReturn([
                [
                    'id' => 'pm_test_123',
                    'brand' => 'visa',
                    'last_four' => '4242',
                    'exp_month' => 12,
                    'exp_year' => 2027,
                    'is_default' => false,
                ],
            ]);
    });

    $this->actingAs($this->user, 'stateful-api')
        ->getJson('/api/v2/billing/me/payment-methods')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'brand', 'last_four', 'exp_month', 'exp_year', 'is_default'],
            ],
        ]);
});

it('marks the default payment method with is_default true', function () {
    $this->mock(BillingPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('listPaymentMethods')
            ->once()
            ->andReturn([
                [
                    'id' => 'pm_default',
                    'brand' => 'mastercard',
                    'last_four' => '5555',
                    'exp_month' => 6,
                    'exp_year' => 2026,
                    'is_default' => true,
                ],
                [
                    'id' => 'pm_other',
                    'brand' => 'visa',
                    'last_four' => '4242',
                    'exp_month' => 12,
                    'exp_year' => 2027,
                    'is_default' => false,
                ],
            ]);
    });

    $response = $this->actingAs($this->user, 'stateful-api')
        ->getJson('/api/v2/billing/me/payment-methods')
        ->assertStatus(200);

    $data = $response->json('data');
    expect($data[0]['is_default'])->toBeTrue()
        ->and($data[1]['is_default'])->toBeFalse();
});

// ─── CREATE SETUP INTENT ──────────────────────────────────────────────────────

it('creates a setup intent and returns client_secret', function () {
    $this->mock(BillingPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('createOrGetCustomer')->once();
        $mock->shouldReceive('createSetupIntent')
            ->once()
            ->andReturn('seti_secret_test_abc');
    });

    $this->actingAs($this->user, 'stateful-api')
        ->postJson('/api/v2/billing/me/setup-intent')
        ->assertStatus(200)
        ->assertJsonPath('data.client_secret', 'seti_secret_test_abc');
});

it('lazily creates a Stripe customer on setup intent if none exists', function () {
    $this->mock(BillingPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('createOrGetCustomer')
            ->once()
            ->with($this->user->id);
        $mock->shouldReceive('createSetupIntent')
            ->once()
            ->andReturn('seti_secret_lazy');
    });

    $this->actingAs($this->user, 'stateful-api')
        ->postJson('/api/v2/billing/me/setup-intent')
        ->assertStatus(200)
        ->assertJsonPath('data.client_secret', 'seti_secret_lazy');
});

// ─── SET DEFAULT PAYMENT METHOD ───────────────────────────────────────────────

it('sets a payment method as default', function () {
    $this->mock(BillingPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('paymentMethodBelongsToUser')
            ->once()
            ->with($this->user->id, 'pm_test_123')
            ->andReturn(true);
        $mock->shouldReceive('setDefaultPaymentMethod')
            ->once()
            ->with($this->user->id, 'pm_test_123');
    });

    $this->actingAs($this->user, 'stateful-api')
        ->putJson('/api/v2/billing/me/payment-methods/pm_test_123/default')
        ->assertStatus(200)
        ->assertJsonPath('data.id', 'pm_test_123')
        ->assertJsonPath('data.is_default', true);
});

it('returns 404 when setting non-existent payment method as default', function () {
    $this->mock(BillingPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('paymentMethodBelongsToUser')
            ->once()
            ->with($this->user->id, 'pm_nonexistent')
            ->andReturn(false);
    });

    $this->actingAs($this->user, 'stateful-api')
        ->putJson('/api/v2/billing/me/payment-methods/pm_nonexistent/default')
        ->assertStatus(404);
});

// ─── DELETE PAYMENT METHOD ────────────────────────────────────────────────────

it('deletes a payment method', function () {
    $this->mock(BillingPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('paymentMethodBelongsToUser')
            ->once()
            ->with($this->user->id, 'pm_to_delete')
            ->andReturn(true);
        $mock->shouldReceive('hasActiveSubscriptions')
            ->once()
            ->with($this->user->id)
            ->andReturn(false);
        $mock->shouldReceive('deletePaymentMethod')
            ->once()
            ->with($this->user->id, 'pm_to_delete');
    });

    $this->actingAs($this->user, 'stateful-api')
        ->deleteJson('/api/v2/billing/me/payment-methods/pm_to_delete')
        ->assertStatus(204);
});

it('returns 404 when deleting non-existent payment method', function () {
    $this->mock(BillingPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('paymentMethodBelongsToUser')
            ->once()
            ->with($this->user->id, 'pm_ghost')
            ->andReturn(false);
    });

    $this->actingAs($this->user, 'stateful-api')
        ->deleteJson('/api/v2/billing/me/payment-methods/pm_ghost')
        ->assertStatus(404);
});

it('returns 422 when deleting default PM with active subscriptions', function () {
    $this->mock(BillingPort::class, function (MockInterface $mock) {
        $mock->shouldReceive('paymentMethodBelongsToUser')
            ->once()
            ->andReturn(true);
        $mock->shouldReceive('hasActiveSubscriptions')
            ->once()
            ->andReturn(true);
    });

    $this->actingAs($this->user, 'stateful-api')
        ->deleteJson('/api/v2/billing/me/payment-methods/pm_active_sub')
        ->assertStatus(422);
});

// ─── AUTHENTICATION ───────────────────────────────────────────────────────────

it('returns 401 for unauthenticated requests on all endpoints', function () {
    $this->getJson('/api/v2/billing/me/payment-methods')->assertStatus(401);
    $this->postJson('/api/v2/billing/me/setup-intent')->assertStatus(401);
    $this->putJson('/api/v2/billing/me/payment-methods/pm_123/default')->assertStatus(401);
    $this->deleteJson('/api/v2/billing/me/payment-methods/pm_123')->assertStatus(401);
});
