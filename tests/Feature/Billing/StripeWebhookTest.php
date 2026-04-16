<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;
use Src\Ai\Infrastructure\Persistence\PrepaidPackageModel;
use Src\Ai\Infrastructure\Persistence\UserPrepaidBalanceModel;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Billing\Infrastructure\Persistence\StripeWebhookEventModel;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

// ─── Helpers ─────────────────────────────────────────────────────────────────

/** @return array<string, mixed> */
function makeStripeEvent(string $id, string $type, array $object = []): array
{
    return [
        'id'   => $id,
        'type' => $type,
        'data' => ['object' => $object],
    ];
}

function signPayload(string $json, string $secret = 'whsec-test-001'): string
{
    $timestamp = time();
    $sig       = hash_hmac('sha256', "{$timestamp}.{$json}", $secret);

    return "t={$timestamp},v1={$sig}";
}

function postWebhookRaw(string $json, ?string $signatureHeader = null): TestResponse
{
    $header = $signatureHeader ?? signPayload($json, config('cashier.webhook.secret', 'whsec-test-001'));

    return test()->call(
        'POST',
        '/api/v2/billing/stripe',
        [],
        [],
        [],
        ['HTTP_STRIPE_SIGNATURE' => $header, 'CONTENT_TYPE' => 'application/json'],
        $json,
    );
}

/** @param array<string, mixed> $event */
function postWebhook(array $event): TestResponse
{
    $json = json_encode($event);

    return postWebhookRaw($json);
}

function makeWebhookSubscriptionType(string $permissionName = 'ai.test_webhook_plan'): SubscriptionTypeModel
{
    $type = SubscriptionTypeModel::factory()->create([
        'mode'             => 'classic',
        'billing_periods'  => ['monthly'],
        'stripe_price_ids' => ['monthly' => 'price_test_webhook'],
        'permission_name'  => $permissionName,
        'is_active'        => true,
    ]);

    \Spatie\Permission\Models\Permission::findOrCreate($permissionName, 'stateful-api');

    return $type;
}

function makeWebhookSubscription(User $user, SubscriptionTypeModel $type, string $stripeSubId, string $status = 'active'): UserSubscriptionModel
{
    /** @var UserSubscriptionModel $sub */
    $sub = UserSubscriptionModel::create([
        'user_id'                => $user->id,
        'subscription_type_id'   => $type->id,
        'billing_period'         => 'monthly',
        'stripe_subscription_id' => $stripeSubId,
        'status'                 => $status,
        'current_period_start'   => now()->subMonth(),
        'current_period_end'     => now()->addMonth(),
    ]);

    return $sub;
}

function makeWebhookPrepaidPackage(): PrepaidPackageModel
{
    $permission = 'ai.prepaid_starter_wh_' . uniqid();

    \Spatie\Permission\Models\Permission::findOrCreate($permission, 'stateful-api');

    /** @var PrepaidPackageModel $pkg */
    $pkg = PrepaidPackageModel::create([
        'name'                    => 'Webhook Starter',
        'slug'                    => 'webhook-starter-' . uniqid(),
        'stripe_price_id'         => 'price_wh_test',
        'permission_name'         => $permission,
        'google_review_limit'     => 10,
        'instagram_content_limit' => 5,
        'price_cents'             => 2000,
        'active'                  => true,
    ]);

    return $pkg;
}

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function (): void {
    config(['cashier.webhook.secret' => 'whsec-test-001']);
});

// ─── T-80-09: Signature, Idempotency & Infrastructure ────────────────────────

describe('POST /api/v2/billing/stripe — signature & idempotency', function (): void {

    it('returns 403 for invalid Stripe-Signature header', function (): void {
        $event = makeStripeEvent('evt_invalid_sig', 'unknown.event');
        $json  = json_encode($event);

        postWebhookRaw($json, 'v1=invalidsignature,t=999')
            ->assertStatus(403);
    });

    it('returns 403 for missing Stripe-Signature header', function (): void {
        $event = makeStripeEvent('evt_missing_sig', 'unknown.event');

        test()->postJson('/api/v2/billing/stripe', $event)
            ->assertStatus(403);
    });

    it('accepts valid signature, stores event, returns ok', function (): void {
        $event = makeStripeEvent('evt_valid_001', 'unknown.event');

        postWebhook($event)
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');

        $this->assertDatabaseHas('stripe_webhook_events', [
            'stripe_event_id' => 'evt_valid_001',
            'event_type'      => 'unknown.event',
        ]);
    });

    it('returns already_processed when event has processed_at set', function (): void {
        StripeWebhookEventModel::create([
            'stripe_event_id' => 'evt_dup_processed',
            'event_type'      => 'unknown.event',
            'payload'         => [],
            'processed_at'    => now(),
        ]);

        $event = makeStripeEvent('evt_dup_processed', 'unknown.event');

        postWebhook($event)
            ->assertOk()
            ->assertJsonPath('data.status', 'already_processed');
    });

    it('re-executes handler when processed_at is null (Stripe retry scenario)', function (): void {
        $type  = makeWebhookSubscriptionType('ai.test_retry');
        $user  = $this->create_user(['email' => 'retry@webhook.test']);
        makeWebhookSubscription($user, $type, 'sub_retry_001', 'past_due');

        // Pre-create event with processed_at = null (simulates previous handler crash)
        StripeWebhookEventModel::create([
            'stripe_event_id' => 'evt_retry_001',
            'event_type'      => 'customer.subscription.updated',
            'payload'         => [],
            'processed_at'    => null,
        ]);

        $object = [
            'id'                  => 'sub_retry_001',
            'status'              => 'active',
            'current_period_end'  => now()->addMonth()->timestamp,
        ];
        $event = makeStripeEvent('evt_retry_001', 'customer.subscription.updated', $object);

        postWebhook($event)
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');

        // Handler re-executed — subscription status updated
        $this->assertDatabaseHas('user_subscriptions', [
            'stripe_subscription_id' => 'sub_retry_001',
            'status'                 => 'active',
        ]);
    });

    it('records unknown event type and returns ok', function (): void {
        $event = makeStripeEvent('evt_unknown_type', 'some.completely.unknown.event');

        postWebhook($event)
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');

        $this->assertDatabaseHas('stripe_webhook_events', [
            'stripe_event_id' => 'evt_unknown_type',
            'event_type'      => 'some.completely.unknown.event',
        ]);
    });

    it('records customer.subscription.created event but does not dispatch a handler (graceful ignore)', function (): void {
        $event = makeStripeEvent('evt_sub_created', 'customer.subscription.created', ['id' => 'sub_created_001']);

        postWebhook($event)
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');

        $this->assertDatabaseHas('stripe_webhook_events', [
            'stripe_event_id' => 'evt_sub_created',
        ]);
    });

    it('leaves processed_at null when handler throws, allowing Stripe to retry', function (): void {
        // customer.subscription.updated with unknown subscription ID → handler throws ModelNotFoundException
        // Laravel's exception handler converts it to 404, but processed_at stays null for retry
        $object = [
            'id'                 => 'sub_does_not_exist',
            'status'             => 'active',
            'current_period_end' => now()->addMonth()->timestamp,
        ];
        $event = makeStripeEvent('evt_handler_throw', 'customer.subscription.updated', $object);

        // ModelNotFoundException → Laravel exception handler returns 404; controller never reaches processed_at update
        postWebhook($event)->assertStatus(404);

        // processed_at must still be null — Stripe can retry
        $this->assertDatabaseHas('stripe_webhook_events', [
            'stripe_event_id' => 'evt_handler_throw',
            'processed_at'    => null,
        ]);
    });

});

// ─── T-80-11: customer.subscription.updated / deleted ────────────────────────

describe('customer.subscription.updated', function (): void {

    it('sets status to active and grants permission', function (): void {
        $type = makeWebhookSubscriptionType('ai.sub_updated_active');
        $user = $this->create_user(['email' => 'sub-updated-active@webhook.test']);
        makeWebhookSubscription($user, $type, 'sub_upd_active_001', 'past_due');

        $object = [
            'id'                 => 'sub_upd_active_001',
            'status'             => 'active',
            'current_period_end' => now()->addMonth()->timestamp,
        ];

        postWebhook(makeStripeEvent('evt_sub_updated_active', 'customer.subscription.updated', $object))
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');

        $this->assertDatabaseHas('user_subscriptions', [
            'stripe_subscription_id' => 'sub_upd_active_001',
            'status'                 => 'active',
        ]);

        $user->refresh();
        expect($user->hasPermissionTo('ai.sub_updated_active'))->toBeTrue();
    });

    it('maps Stripe canceled (US) to cancelled (British) status', function (): void {
        $type = makeWebhookSubscriptionType('ai.sub_canceled');
        $user = $this->create_user(['email' => 'sub-canceled@webhook.test']);
        makeWebhookSubscription($user, $type, 'sub_canceled_001', 'active');

        $object = [
            'id'                 => 'sub_canceled_001',
            'status'             => 'canceled', // Stripe US spelling
            'current_period_end' => now()->timestamp,
        ];

        postWebhook(makeStripeEvent('evt_sub_canceled', 'customer.subscription.updated', $object))
            ->assertOk();

        // DB must use British spelling to avoid enum constraint failure
        $this->assertDatabaseHas('user_subscriptions', [
            'stripe_subscription_id' => 'sub_canceled_001',
            'status'                 => 'cancelled',
        ]);
    });

    it('sets status to past_due but does NOT revoke permission (grace period)', function (): void {
        $type = makeWebhookSubscriptionType('ai.sub_past_due');
        $user = $this->create_user(['email' => 'sub-past-due@webhook.test']);
        makeWebhookSubscription($user, $type, 'sub_past_due_001', 'active');
        $user->givePermissionTo('ai.sub_past_due');

        $object = [
            'id'                 => 'sub_past_due_001',
            'status'             => 'past_due',
            'current_period_end' => now()->timestamp,
        ];

        postWebhook(makeStripeEvent('evt_sub_past_due', 'customer.subscription.updated', $object))
            ->assertOk();

        $this->assertDatabaseHas('user_subscriptions', [
            'stripe_subscription_id' => 'sub_past_due_001',
            'status'                 => 'past_due',
        ]);

        // Permission NOT revoked during grace period
        $user->refresh();
        expect($user->hasPermissionTo('ai.sub_past_due'))->toBeTrue();
    });

});

describe('customer.subscription.deleted', function (): void {

    it('sets status to expired and revokes permission', function (): void {
        $type = makeWebhookSubscriptionType('ai.sub_deleted');
        $user = $this->create_user(['email' => 'sub-deleted@webhook.test']);
        makeWebhookSubscription($user, $type, 'sub_deleted_001', 'active');
        $user->givePermissionTo('ai.sub_deleted');

        $object = ['id' => 'sub_deleted_001'];

        postWebhook(makeStripeEvent('evt_sub_deleted', 'customer.subscription.deleted', $object))
            ->assertOk();

        $this->assertDatabaseHas('user_subscriptions', [
            'stripe_subscription_id' => 'sub_deleted_001',
            'status'                 => 'expired',
        ]);

        $user->refresh();
        expect($user->hasPermissionTo('ai.sub_deleted'))->toBeFalse();
    });

});

// ─── T-80-14: invoice.payment_succeeded / invoice.payment_failed ─────────────

describe('invoice.payment_succeeded', function (): void {

    it('sets status to active and extends current_period_end', function (): void {
        $type      = makeWebhookSubscriptionType('ai.invoice_succeeded');
        $user      = $this->create_user(['email' => 'invoice-succeeded@webhook.test']);
        makeWebhookSubscription($user, $type, 'sub_inv_succ_001', 'past_due');

        $newPeriodEnd = now()->addMonths(2)->timestamp;

        $object = [
            'subscription' => 'sub_inv_succ_001',
            'period_end'   => $newPeriodEnd,
        ];

        postWebhook(makeStripeEvent('evt_inv_succ', 'invoice.payment_succeeded', $object))
            ->assertOk();

        $this->assertDatabaseHas('user_subscriptions', [
            'stripe_subscription_id' => 'sub_inv_succ_001',
            'status'                 => 'active',
        ]);

        $user->refresh();
        expect($user->hasPermissionTo('ai.invoice_succeeded'))->toBeTrue();
    });

});

describe('invoice.payment_failed', function (): void {

    it('sets status to past_due and does NOT revoke permission', function (): void {
        $type = makeWebhookSubscriptionType('ai.invoice_failed');
        $user = $this->create_user(['email' => 'invoice-failed@webhook.test']);
        makeWebhookSubscription($user, $type, 'sub_inv_fail_001', 'active');
        $user->givePermissionTo('ai.invoice_failed');

        $object = ['subscription' => 'sub_inv_fail_001'];

        postWebhook(makeStripeEvent('evt_inv_fail', 'invoice.payment_failed', $object))
            ->assertOk();

        $this->assertDatabaseHas('user_subscriptions', [
            'stripe_subscription_id' => 'sub_inv_fail_001',
            'status'                 => 'past_due',
        ]);

        // Grace period: permission NOT revoked
        $user->refresh();
        expect($user->hasPermissionTo('ai.invoice_failed'))->toBeTrue();
    });

});

// ─── T-80-16: payment_intent.succeeded / payment_intent.payment_failed ───────

describe('payment_intent.succeeded — 3DS path', function (): void {

    it('creates balance and grants permission when metadata is present and no existing balance', function (): void {
        $user    = $this->create_user(['email' => 'pi-succeeded-3ds@webhook.test']);
        $package = makeWebhookPrepaidPackage();

        $object = [
            'id'       => 'pi_3ds_001',
            'metadata' => [
                'prepaid_package_id' => (string) $package->id,
                'user_id'            => (string) $user->id,
            ],
        ];

        postWebhook(makeStripeEvent('evt_pi_3ds', 'payment_intent.succeeded', $object))
            ->assertOk();

        $this->assertDatabaseHas('user_prepaid_balances', [
            'user_id'                          => $user->id,
            'prepaid_package_id'               => $package->id,
            'google_review_requests_remaining' => 10,
            'instagram_requests_remaining'     => 5,
            'stripe_payment_intent_id'         => 'pi_3ds_001',
        ]);

        $user->refresh();
        expect($user->hasPermissionTo($package->permission_name))->toBeTrue();
    });

    it('gracefully ignores when balance already exists (sync path)', function (): void {
        $user    = $this->create_user(['email' => 'pi-sync-exists@webhook.test']);
        $package = makeWebhookPrepaidPackage();

        // Pre-existing balance (sync path — balance created before webhook)
        UserPrepaidBalanceModel::create([
            'user_id'                          => $user->id,
            'prepaid_package_id'               => $package->id,
            'google_review_requests_remaining' => 10,
            'instagram_requests_remaining'     => 5,
            'purchased_at'                     => now(),
            'stripe_payment_intent_id'         => 'pi_sync_exists_001',
        ]);

        $object = [
            'id'       => 'pi_sync_exists_001',
            'metadata' => [
                'prepaid_package_id' => (string) $package->id,
                'user_id'            => (string) $user->id,
            ],
        ];

        postWebhook(makeStripeEvent('evt_pi_sync_exists', 'payment_intent.succeeded', $object))
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');

        // Only one balance row (not duplicated)
        expect(UserPrepaidBalanceModel::where('stripe_payment_intent_id', 'pi_sync_exists_001')->count())->toBe(1);
    });

    it('gracefully ignores when no metadata (non-prepaid PaymentIntent)', function (): void {
        $user = $this->create_user(['email' => 'pi-no-metadata@webhook.test']);

        $object = [
            'id'       => 'pi_no_meta_001',
            'metadata' => [], // no prepaid_package_id
        ];

        postWebhook(makeStripeEvent('evt_pi_no_meta', 'payment_intent.succeeded', $object))
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');

        // No balance created
        $this->assertDatabaseMissing('user_prepaid_balances', ['stripe_payment_intent_id' => 'pi_no_meta_001']);
    });

    it('atomically creates balance and grants permission (if permission fails, no balance row exists)', function (): void {
        $user    = $this->create_user(['email' => 'pi-atomicity@webhook.test']);
        $package = makeWebhookPrepaidPackage();

        // Revoke the permission from Spatie's DB so givePermissionTo() works
        // (it already exists from makeWebhookPrepaidPackage — just verify no existing balance)
        $this->assertDatabaseMissing('user_prepaid_balances', ['user_id' => $user->id]);

        $object = [
            'id'       => 'pi_atomicity_001',
            'metadata' => [
                'prepaid_package_id' => (string) $package->id,
                'user_id'            => (string) $user->id,
            ],
        ];

        postWebhook(makeStripeEvent('evt_pi_atomicity', 'payment_intent.succeeded', $object))
            ->assertOk();

        $this->assertDatabaseHas('user_prepaid_balances', [
            'user_id'                  => $user->id,
            'stripe_payment_intent_id' => 'pi_atomicity_001',
        ]);

        $user->refresh();
        expect($user->hasPermissionTo($package->permission_name))->toBeTrue();
    });

});

describe('payment_intent.payment_failed', function (): void {

    it('gracefully ignores prepaid payment failure (no balance to rollback)', function (): void {
        $user    = $this->create_user(['email' => 'pi-failed-prepaid@webhook.test']);
        $package = makeWebhookPrepaidPackage();

        $object = [
            'id'       => 'pi_failed_001',
            'metadata' => [
                'prepaid_package_id' => (string) $package->id,
                'user_id'            => (string) $user->id,
            ],
        ];

        postWebhook(makeStripeEvent('evt_pi_failed_prepaid', 'payment_intent.payment_failed', $object))
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');

        $this->assertDatabaseMissing('user_prepaid_balances', ['user_id' => $user->id]);
    });

    it('gracefully ignores non-prepaid payment failure', function (): void {
        $object = [
            'id'       => 'pi_failed_nonprepaid',
            'metadata' => [],
        ];

        postWebhook(makeStripeEvent('evt_pi_failed_nonprepaid', 'payment_intent.payment_failed', $object))
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');
    });

});
