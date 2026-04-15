<?php

declare(strict_types=1);

namespace Src\Ai\Application\Services;

use App\Models\User;
use Src\Ai\Infrastructure\Persistence\AiUsageRecordModel;
use Src\Ai\Infrastructure\Persistence\UserPrepaidBalanceModel;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Identity\Domain\Permissions\DomainPermissions;

/**
 * Application service for per-feature AI quota enforcement.
 *
 * Tier priority (first match wins):
 *   1. Prepaid   — per-feature remaining balance (no monthly reset)
 *   2. Standard  — classic subscription, monthly per-feature limit
 *   3. Basic     — classic subscription, monthly per-feature limit
 *   4. Free      — config-driven monthly per-feature limit (resets monthly)
 *   5. None      — returns 0
 *
 * Lives in Application\Services (not Domain\Services) because it queries
 * Eloquent models directly — an Infrastructure concern. See filocian/msgns-api#81.
 */
final class AiUsageLimitsService
{
    /** @param 'google_reviews'|'instagram' $productType */
    public function getRemainingQuota(User $user, string $productType): int
    {
        if ($this->hasPrepaidPermission($user)) {
            return $this->getPrepaidRemaining($user, $productType);
        }

        if ($this->hasStandardPermission($user)) {
            return $this->getClassicRemaining($user, $productType);
        }

        if ($this->hasBasicPermission($user)) {
            return $this->getClassicRemaining($user, $productType);
        }

        if ($user->can(DomainPermissions::AI_FREE_PREVIEW)) {
            return $this->getFreeRemaining($user, $productType);
        }

        return 0;
    }

    /** @param 'google_reviews'|'instagram' $productType */
    public function hasQuota(User $user, string $productType): bool
    {
        return $this->getRemainingQuota($user, $productType) > 0;
    }

    private function hasPrepaidPermission(User $user): bool
    {
        return $user->can(DomainPermissions::AI_PREPAID_STARTER)
            || $user->can(DomainPermissions::AI_PREPAID_GROWTH)
            || $user->can(DomainPermissions::AI_PREPAID_PRO);
    }

    private function hasStandardPermission(User $user): bool
    {
        return $user->can(DomainPermissions::AI_STANDARD_MONTHLY)
            || $user->can(DomainPermissions::AI_STANDARD_YEARLY);
    }

    private function hasBasicPermission(User $user): bool
    {
        return $user->can(DomainPermissions::AI_BASIC_MONTHLY)
            || $user->can(DomainPermissions::AI_BASIC_YEARLY);
    }

    /** @param 'google_reviews'|'instagram' $productType */
    private function getPrepaidRemaining(User $user, string $productType): int
    {
        $column = $this->prepaidRemainingColumn($productType);

        return (int) UserPrepaidBalanceModel::where('user_id', $user->id)
            ->where($column, '>', 0)
            ->sum($column);
    }

    /** @param 'google_reviews'|'instagram' $productType */
    private function getActiveSubscription(User $user): ?UserSubscriptionModel
    {
        return UserSubscriptionModel::where('user_id', $user->id)
            ->whereIn('status', ['active', 'past_due'])
            ->first();
    }

    /** @param 'google_reviews'|'instagram' $productType */
    private function getClassicRemaining(User $user, string $productType): int
    {
        $subscription = $this->getActiveSubscription($user);

        if ($subscription === null) {
            return 0;
        }

        $limit = match ($productType) {
            'google_reviews' => $subscription->subscriptionType->google_review_limit,
            'instagram'      => $subscription->subscriptionType->instagram_content_limit,
        };

        $count = $this->getMonthlyClassicUsageCount($user->id, $subscription->id, $productType);

        return max(0, $limit - $count);
    }

    /** @param 'google_reviews'|'instagram' $productType */
    private function getFreeRemaining(User $user, string $productType): int
    {
        $limit = match ($productType) {
            'google_reviews' => (int) config('services.gemini.ai_free_google_review_requests_per_month', 5),
            'instagram'      => (int) config('services.gemini.ai_free_instagram_requests_per_month', 5),
        };

        $count = $this->getMonthlyFreeUsageCount($user->id, $productType);

        return max(0, $limit - $count);
    }

    /** @param 'google_reviews'|'instagram' $productType */
    private function getMonthlyClassicUsageCount(int $userId, int $subscriptionId, string $productType): int
    {
        return AiUsageRecordModel::where('user_id', $userId)
            ->where('source', 'classic')
            ->where('product_type', $productType)
            ->where('user_subscription_id', $subscriptionId)
            ->whereBetween('used_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /** @param 'google_reviews'|'instagram' $productType */
    private function getMonthlyFreeUsageCount(int $userId, string $productType): int
    {
        return AiUsageRecordModel::where('user_id', $userId)
            ->where('source', 'free')
            ->where('product_type', $productType)
            ->whereBetween('used_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /**
     * @param 'google_reviews'|'instagram' $productType
     * @return 'google_review_requests_remaining'|'instagram_requests_remaining'
     */
    private function prepaidRemainingColumn(string $productType): string
    {
        return match ($productType) {
            'google_reviews' => 'google_review_requests_remaining',
            'instagram'      => 'instagram_requests_remaining',
        };
    }
}
