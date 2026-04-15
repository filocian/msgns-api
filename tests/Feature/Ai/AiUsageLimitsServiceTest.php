<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Src\Ai\Application\Services\AiUsageLimitsService;
use Src\Ai\Infrastructure\Persistence\AiUsageRecordModel;
use Src\Ai\Infrastructure\Persistence\PrepaidPackageModel;
use Src\Ai\Infrastructure\Persistence\UserPrepaidBalanceModel;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeAiPermission(string $name): void
{
    Permission::findOrCreate($name, 'stateful-api');
}

function makeUserWithPermission(string $email, string $permission): User
{
    makeAiPermission($permission);
    $user = User::factory()->create(['email' => $email]);
    $user->givePermissionTo($permission);

    return $user;
}

/** @param 'google_reviews'|'instagram' $productType */
function recordFreeUsage(int $userId, string $productType, int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        AiUsageRecordModel::query()->create([
            'user_id'      => $userId,
            'source'       => 'free',
            'product_type' => $productType,
            'used_at'      => now(),
        ]);
    }
}

/** @param 'google_reviews'|'instagram' $productType */
function recordClassicUsage(int $userId, int $subscriptionId, string $productType, int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        AiUsageRecordModel::query()->create([
            'user_id'             => $userId,
            'source'              => 'classic',
            'product_type'        => $productType,
            'user_subscription_id' => $subscriptionId,
            'used_at'             => now(),
        ]);
    }
}

function makeClassicSubscription(User $user, int $googleReviewLimit, int $instagramLimit, string $status = 'active'): UserSubscriptionModel
{
    $type = SubscriptionTypeModel::factory()->create([
        'mode'                    => 'classic',
        'google_review_limit'     => $googleReviewLimit,
        'instagram_content_limit' => $instagramLimit,
    ]);

    /** @var UserSubscriptionModel */
    return UserSubscriptionModel::factory()->create([
        'user_id'               => $user->id,
        'subscription_type_id'  => $type->id,
        'status'                => $status,
    ]);
}

function makeMinimalPrepaidPackage(): PrepaidPackageModel
{
    /** @var PrepaidPackageModel */
    return PrepaidPackageModel::query()->create([
        'name'                    => 'Test Package',
        'slug'                    => 'test-pkg-' . uniqid(),
        'stripe_price_id'         => 'price_test',
        'permission_name'         => 'ai.prepaid_starter',
        'google_review_limit'     => 100,
        'instagram_content_limit' => 100,
        'price_cents'             => 1000,
        'active'                  => true,
    ]);
}

function makePrepaidBalance(User $user, int $googleRemaining, int $instagramRemaining): UserPrepaidBalanceModel
{
    $package = makeMinimalPrepaidPackage();

    /** @var UserPrepaidBalanceModel */
    return UserPrepaidBalanceModel::query()->create([
        'user_id'                          => $user->id,
        'prepaid_package_id'               => $package->id,
        'google_review_requests_remaining' => $googleRemaining,
        'instagram_requests_remaining'     => $instagramRemaining,
        'purchased_at'                     => now(),
        'stripe_payment_intent_id'         => 'pi_test_' . uniqid(),
    ]);
}

// ─── AiUsageLimitsService ─────────────────────────────────────────────────────

describe('AiUsageLimitsService', function (): void {

    beforeEach(function (): void {
        $this->service = app(AiUsageLimitsService::class);
    });

    // ── Free tier ──────────────────────────────────────────────────────────────

    it('returns remaining free quota when usage is below monthly limit', function (): void {
        config(['services.gemini.ai_free_google_review_requests_per_month' => 5]);

        $user = makeUserWithPermission('free-within@test.com', DomainPermissions::AI_FREE_PREVIEW);
        recordFreeUsage($user->id, 'google_reviews', 2);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(3);
    });

    it('returns 0 when free monthly limit is exhausted', function (): void {
        config(['services.gemini.ai_free_google_review_requests_per_month' => 5]);

        $user = makeUserWithPermission('free-at-limit@test.com', DomainPermissions::AI_FREE_PREVIEW);
        recordFreeUsage($user->id, 'google_reviews', 5);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(0);
    });

    it('tracks free quota separately per product type', function (): void {
        config(['services.gemini.ai_free_google_review_requests_per_month' => 5]);
        config(['services.gemini.ai_free_instagram_requests_per_month' => 3]);

        $user = makeUserWithPermission('free-per-type@test.com', DomainPermissions::AI_FREE_PREVIEW);
        recordFreeUsage($user->id, 'google_reviews', 4);
        recordFreeUsage($user->id, 'instagram', 1);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(1);
        expect($this->service->getRemainingQuota($user, 'instagram'))->toBe(2);
    });

    // ── Classic tier ───────────────────────────────────────────────────────────

    it('returns remaining classic quota when usage is below subscription limit', function (): void {
        $user         = makeUserWithPermission('classic-within@test.com', DomainPermissions::AI_STANDARD_MONTHLY);
        $subscription = makeClassicSubscription($user, googleReviewLimit: 10, instagramLimit: 5);
        recordClassicUsage($user->id, $subscription->id, 'google_reviews', 3);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(7);
    });

    it('returns 0 when classic monthly subscription limit is exhausted', function (): void {
        $user         = makeUserWithPermission('classic-at-limit@test.com', DomainPermissions::AI_BASIC_MONTHLY);
        $subscription = makeClassicSubscription($user, googleReviewLimit: 10, instagramLimit: 5);
        recordClassicUsage($user->id, $subscription->id, 'google_reviews', 10);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(0);
    });

    it('returns 0 when user has classic permission but no subscription row', function (): void {
        $user = makeUserWithPermission('classic-no-sub@test.com', DomainPermissions::AI_STANDARD_MONTHLY);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(0);
    });

    it('treats past_due subscription as active', function (): void {
        $user         = makeUserWithPermission('classic-past-due@test.com', DomainPermissions::AI_STANDARD_YEARLY);
        $subscription = makeClassicSubscription($user, googleReviewLimit: 20, instagramLimit: 10, status: 'past_due');
        recordClassicUsage($user->id, $subscription->id, 'google_reviews', 5);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(15);
    });

    it('returns 0 for cancelled subscription', function (): void {
        $user = makeUserWithPermission('classic-cancelled@test.com', DomainPermissions::AI_STANDARD_MONTHLY);
        makeClassicSubscription($user, googleReviewLimit: 20, instagramLimit: 10, status: 'cancelled');

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(0);
    });

    // ── Prepaid tier ───────────────────────────────────────────────────────────

    it('returns sum of prepaid google review balance', function (): void {
        $user = makeUserWithPermission('prepaid-balance@test.com', DomainPermissions::AI_PREPAID_STARTER);
        makePrepaidBalance($user, googleRemaining: 25, instagramRemaining: 10);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(25);
        expect($this->service->getRemainingQuota($user, 'instagram'))->toBe(10);
    });

    it('returns 0 when prepaid balance is fully depleted', function (): void {
        $user = makeUserWithPermission('prepaid-empty@test.com', DomainPermissions::AI_PREPAID_GROWTH);
        makePrepaidBalance($user, googleRemaining: 0, instagramRemaining: 0);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(0);
    });

    it('sums across multiple prepaid balance rows', function (): void {
        $user = makeUserWithPermission('prepaid-multi@test.com', DomainPermissions::AI_PREPAID_PRO);
        makePrepaidBalance($user, googleRemaining: 10, instagramRemaining: 0);
        makePrepaidBalance($user, googleRemaining: 15, instagramRemaining: 5);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(25);
    });

    // ── Tier priority ──────────────────────────────────────────────────────────

    it('prepaid tier takes priority over classic when user has both permissions', function (): void {
        makeAiPermission(DomainPermissions::AI_PREPAID_STARTER);
        makeAiPermission(DomainPermissions::AI_STANDARD_MONTHLY);

        $user = User::factory()->create(['email' => 'priority-prepaid@test.com']);
        $user->givePermissionTo(DomainPermissions::AI_PREPAID_STARTER);
        $user->givePermissionTo(DomainPermissions::AI_STANDARD_MONTHLY);

        // Classic has quota
        makeClassicSubscription($user, googleReviewLimit: 100, instagramLimit: 50);
        // Prepaid is depleted
        makePrepaidBalance($user, googleRemaining: 0, instagramRemaining: 0);

        // Prepaid wins — returns 0 even though classic has quota
        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(0);
    });

    // ── No permission ──────────────────────────────────────────────────────────

    it('returns 0 when user has no AI permission', function (): void {
        $user = User::factory()->create(['email' => 'no-permission@test.com']);

        expect($this->service->getRemainingQuota($user, 'google_reviews'))->toBe(0);
        expect($this->service->hasQuota($user, 'google_reviews'))->toBeFalse();
    });

    // ── hasQuota convenience method ────────────────────────────────────────────

    it('hasQuota returns true when quota is available', function (): void {
        config(['services.gemini.ai_free_google_review_requests_per_month' => 5]);

        $user = makeUserWithPermission('has-quota-true@test.com', DomainPermissions::AI_FREE_PREVIEW);

        expect($this->service->hasQuota($user, 'google_reviews'))->toBeTrue();
    });

    it('hasQuota returns false when quota is exhausted', function (): void {
        config(['services.gemini.ai_free_google_review_requests_per_month' => 5]);

        $user = makeUserWithPermission('has-quota-false@test.com', DomainPermissions::AI_FREE_PREVIEW);
        recordFreeUsage($user->id, 'google_reviews', 5);

        expect($this->service->hasQuota($user, 'google_reviews'))->toBeFalse();
    });

});
