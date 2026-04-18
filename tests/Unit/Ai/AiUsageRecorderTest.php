<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Src\Ai\Application\Services\AiUsageRecorder;
use Src\Ai\Infrastructure\Persistence\AiUsageRecordModel;
use Src\Ai\Infrastructure\Persistence\UserPrepaidBalanceModel;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Identity\Domain\Permissions\DomainPermissions;

uses(RefreshDatabase::class);

function grantPermission(User $user, string $permission): void
{
    Permission::findOrCreate($permission, 'stateful-api');
    $user->givePermissionTo($permission);
}

describe('AiUsageRecorder', function (): void {

    it('persists a usage row for free tier with source=free and no subscription or prepaid id', function (): void {
        $user = User::factory()->create();
        grantPermission($user, DomainPermissions::AI_FREE_PREVIEW);

        $recorder = app(AiUsageRecorder::class);

        $recorder->record($user->id, 'google_reviews', 123);

        $row = AiUsageRecordModel::where('user_id', $user->id)->firstOrFail();

        expect($row->source)->toBe('free')
            ->and($row->product_type)->toBe('google_reviews')
            ->and($row->tokens_used)->toBe(123)
            ->and($row->user_subscription_id)->toBeNull()
            ->and($row->user_prepaid_balance_id)->toBeNull()
            ->and($row->used_at)->not->toBeNull();
    });

    it('accepts null tokens_used', function (): void {
        $user = User::factory()->create();
        grantPermission($user, DomainPermissions::AI_FREE_PREVIEW);

        $recorder = app(AiUsageRecorder::class);

        $recorder->record($user->id, 'instagram', null);

        $row = AiUsageRecordModel::where('user_id', $user->id)->firstOrFail();

        expect($row->tokens_used)->toBeNull()
            ->and($row->product_type)->toBe('instagram')
            ->and($row->source)->toBe('free');
    });

    it('persists a usage row for classic tier with source=classic and user_subscription_id set', function (): void {
        $user = User::factory()->create();
        grantPermission($user, DomainPermissions::AI_STANDARD_MONTHLY);

        $subscription = UserSubscriptionModel::factory()->create([
            'user_id' => $user->id,
            'status'  => 'active',
        ]);

        $recorder = app(AiUsageRecorder::class);

        $recorder->record($user->id, 'instagram', 500);

        $row = AiUsageRecordModel::where('user_id', $user->id)->firstOrFail();

        expect($row->source)->toBe('classic')
            ->and($row->product_type)->toBe('instagram')
            ->and($row->tokens_used)->toBe(500)
            ->and($row->user_subscription_id)->toBe($subscription->id)
            ->and($row->user_prepaid_balance_id)->toBeNull();
    });

    it('persists a usage row for prepaid tier with source=prepaid and user_prepaid_balance_id set', function (): void {
        $user = User::factory()->create();
        grantPermission($user, DomainPermissions::AI_PREPAID_STARTER);

        $balance = UserPrepaidBalanceModel::create([
            'user_id'                          => $user->id,
            'prepaid_package_id'               => createPrepaidPackage(),
            'google_review_requests_remaining' => 50,
            'instagram_requests_remaining'     => 50,
            'purchased_at'                     => now(),
            'expires_at'                       => now()->addYear(),
            'stripe_payment_intent_id'         => 'pi_test_' . str()->random(16),
        ]);

        $recorder = app(AiUsageRecorder::class);

        $recorder->record($user->id, 'google_reviews', 42);

        $row = AiUsageRecordModel::where('user_id', $user->id)->firstOrFail();

        expect($row->source)->toBe('prepaid')
            ->and($row->product_type)->toBe('google_reviews')
            ->and($row->tokens_used)->toBe(42)
            ->and($row->user_subscription_id)->toBeNull()
            ->and($row->user_prepaid_balance_id)->toBe($balance->id);
    });
});

function createPrepaidPackage(): int
{
    /** @var \Src\Ai\Infrastructure\Persistence\PrepaidPackageModel $pkg */
    $pkg = \Src\Ai\Infrastructure\Persistence\PrepaidPackageModel::create([
        'name'                    => 'Test Starter',
        'slug'                    => 'test-starter-' . str()->random(6),
        'stripe_price_id'         => 'price_test_' . str()->random(8),
        'permission_name'         => DomainPermissions::AI_PREPAID_STARTER,
        'google_review_limit'     => 50,
        'instagram_content_limit' => 50,
        'price_cents'             => 1000,
        'active'                  => true,
    ]);

    return (int) $pkg->id;
}
