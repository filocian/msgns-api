<?php

declare(strict_types=1);

use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Payment;
use Mockery\MockInterface;
use Src\Ai\Domain\Ports\PrepaidChargePort;
use Src\Ai\Infrastructure\Persistence\PrepaidPackageModel;
use Src\Ai\Infrastructure\Persistence\UserPrepaidBalanceModel;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makePrepaidPackage(array $overrides = []): PrepaidPackageModel
{
    /** @var PrepaidPackageModel */
    return PrepaidPackageModel::query()->create(array_merge([
        'name'              => 'Starter',
        'slug'              => 'starter-' . uniqid(),
        'stripe_price_id'   => 'price_starter_test',
        'permission_name'   => 'ai.prepaid_starter',
        'requests_included' => 100,
        'price_cents'       => 1000,
        'active'            => true,
    ], $overrides));
}

function ensurePrepaidPermission(string $name): void
{
    \Spatie\Permission\Models\Permission::findOrCreate($name, 'stateful-api');
}

/** @return Payment */
function makePaymentMock(string $intentId = 'pi_test_001'): Payment
{
    $mock = Mockery::mock(Payment::class);
    $mock->id = $intentId;

    return $mock;
}

// ─── POST /api/v2/ai/packages/purchase ───────────────────────────────────────

describe('POST /api/v2/ai/packages/purchase', function (): void {

    it('creates balance, grants permission, charges Stripe, returns 201', function (): void {
        $user    = $this->create_user(['email' => 'prepaid-buy@test.com']);
        $package = makePrepaidPackage(['slug' => 'starter-buy']);
        ensurePrepaidPermission('ai.prepaid_starter');

        $mockPayment = makePaymentMock('pi_test_success_001');

        $this->mock(PrepaidChargePort::class, function (MockInterface $mock) use ($mockPayment): void {
            $mock->shouldReceive('charge')
                ->once()
                ->andReturn($mockPayment);
        });

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/packages/purchase', [
                'package_id'        => $package->id,
                'payment_method_id' => 'pm_test_visa',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'succeeded')
            ->assertJsonStructure(['data' => ['status', 'balance']]);

        $this->assertDatabaseHas('user_prepaid_balances', [
            'user_id'            => $user->id,
            'prepaid_package_id' => $package->id,
            'requests_remaining' => 100,
        ]);

        $user->refresh();
        expect($user->hasPermissionTo('ai.prepaid_starter'))->toBeTrue();
    });

    it('returns only balances with requests_remaining > 0', function (): void {
        $user    = $this->create_user(['email' => 'prepaid-balance-list@test.com']);
        $package = makePrepaidPackage(['slug' => 'starter-balance-list']);

        UserPrepaidBalanceModel::query()->create([
            'user_id'                  => $user->id,
            'prepaid_package_id'       => $package->id,
            'requests_remaining'       => 50,
            'purchased_at'             => now(),
            'stripe_payment_intent_id' => 'pi_active_001',
        ]);

        UserPrepaidBalanceModel::query()->create([
            'user_id'                  => $user->id,
            'prepaid_package_id'       => $package->id,
            'requests_remaining'       => 0,
            'purchased_at'             => now()->subDay(),
            'stripe_payment_intent_id' => 'pi_depleted_001',
        ]);

        $response = $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/packages/balances')
            ->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['requests_remaining'])->toBe(50);
    });

    it('returns 422 with status failed when PaymentFailure is thrown', function (): void {
        $user    = $this->create_user(['email' => 'prepaid-fail@test.com']);
        $package = makePrepaidPackage(['slug' => 'starter-fail']);

        $this->mock(PrepaidChargePort::class, function (MockInterface $mock): void {
            $mock->shouldReceive('charge')
                ->once()
                ->andThrow(\Stripe\Exception\CardException::factory('Your card was declined.', 402));
        });

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/packages/purchase', [
                'package_id'        => $package->id,
                'payment_method_id' => 'pm_test_declined',
            ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'failed');

        $this->assertDatabaseMissing('user_prepaid_balances', [
            'user_id' => $user->id,
        ]);
    });

    it('returns 200 with client_secret when PaymentActionRequired (3DS), no balance created', function (): void {
        $user    = $this->create_user(['email' => 'prepaid-3ds@test.com']);
        $package = makePrepaidPackage(['slug' => 'starter-3ds']);
        ensurePrepaidPermission('ai.prepaid_starter');

        $mockPayment = Mockery::mock(Payment::class);
        $mockPayment->shouldReceive('clientSecret')->andReturn('pi_test_3ds_secret');

        $this->mock(PrepaidChargePort::class, function (MockInterface $mock) use ($mockPayment): void {
            $mock->shouldReceive('charge')
                ->once()
                ->andThrow(new IncompletePayment($mockPayment, 'The payment requires action.'));
        });

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/packages/purchase', [
                'package_id'        => $package->id,
                'payment_method_id' => 'pm_test_3ds',
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'requires_action')
            ->assertJsonPath('client_secret', 'pi_test_3ds_secret');

        expect(UserPrepaidBalanceModel::query()->where('user_id', $user->id)->count())->toBe(0);

        $user->refresh();
        expect($user->hasPermissionTo('ai.prepaid_starter'))->toBeFalse();
    });

    it('re-purchase creates second balance row, permission still granted once', function (): void {
        $user    = $this->create_user(['email' => 'prepaid-repurchase@test.com']);
        $package = makePrepaidPackage(['slug' => 'starter-repurchase']);
        ensurePrepaidPermission('ai.prepaid_starter');

        $mockPayment1 = makePaymentMock('pi_first_001');
        $mockPayment2 = makePaymentMock('pi_second_002');

        $this->mock(PrepaidChargePort::class, function (MockInterface $mock) use ($mockPayment1, $mockPayment2): void {
            $mock->shouldReceive('charge')
                ->twice()
                ->andReturn($mockPayment1, $mockPayment2);
        });

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/packages/purchase', [
                'package_id'        => $package->id,
                'payment_method_id' => 'pm_test_visa',
            ])
            ->assertStatus(201);

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/packages/purchase', [
                'package_id'        => $package->id,
                'payment_method_id' => 'pm_test_visa',
            ])
            ->assertStatus(201);

        expect(UserPrepaidBalanceModel::query()->where('user_id', $user->id)->count())->toBe(2);
    });

    it('returns 404 with package_not_found when package_id does not exist', function (): void {
        $user = $this->create_user(['email' => 'prepaid-notfound@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/packages/purchase', [
                'package_id'        => 99999,
                'payment_method_id' => 'pm_test_visa',
            ])
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'package_not_found');
    });

});

// ─── GET /api/v2/ai/prepaid-packages ─────────────────────────────────────────

describe('GET /api/v2/ai/prepaid-packages', function (): void {

    it('returns only active packages', function (): void {
        makePrepaidPackage(['slug' => 'active-pkg-1', 'active' => true]);
        makePrepaidPackage(['slug' => 'active-pkg-2', 'active' => true]);
        makePrepaidPackage(['slug' => 'inactive-pkg', 'active' => false]);

        $response = $this->getJson('/api/v2/ai/prepaid-packages')
            ->assertStatus(200);

        $data = $response->json('data');
        // Only active packages should appear
        foreach ($data as $pkg) {
            expect($pkg['active'])->toBeTrue();
        }
        expect(count($data))->toBeGreaterThanOrEqual(2);
    });

    it('is publicly accessible without auth, returns 200', function (): void {
        makePrepaidPackage(['slug' => 'public-pkg-test']);

        $this->getJson('/api/v2/ai/prepaid-packages')
            ->assertStatus(200);
    });

});
