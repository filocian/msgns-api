<?php

declare(strict_types=1);

namespace Src\Ai\Application\Services;

use App\Models\User;
use Src\Ai\Infrastructure\Persistence\AiUsageRecordModel;
use Src\Ai\Infrastructure\Persistence\UserPrepaidBalanceModel;
use Src\Ai\Infrastructure\Persistence\UserSubscriptionModel;
use Src\Identity\Domain\Permissions\DomainPermissions;

/**
 * Persists a single {@see AiUsageRecordModel} row after a successful AI call.
 *
 * Tier resolution mirrors {@see AiUsageLimitsService::getRemainingQuota()}:
 *   1. Prepaid  → source='prepaid', user_prepaid_balance_id=<oldest non-empty balance>
 *   2. Classic  → source='classic', user_subscription_id=<active subscription>
 *   3. Free     → source='free'
 *   4. None     → silently no-op (user has no AI entitlement)
 *
 * Lives in Application\Services because it queries Eloquent models directly —
 * same precedent as AiUsageLimitsService (see filocian/msgns-api#81).
 */
final class AiUsageRecorder
{
    /** @param 'google_reviews'|'instagram' $productType */
    public function record(int $userId, string $productType, ?int $tokensUsed): void
    {
        $user = User::find($userId);

        if ($user === null) {
            return;
        }

        $resolved = $this->resolveSource($user, $productType);

        if ($resolved === null) {
            return;
        }

        AiUsageRecordModel::create([
            'user_id'                 => $userId,
            'source'                  => $resolved['source'],
            'product_type'            => $productType,
            'user_subscription_id'    => $resolved['user_subscription_id'],
            'user_prepaid_balance_id' => $resolved['user_prepaid_balance_id'],
            'used_at'                 => now(),
            'tokens_used'             => $tokensUsed,
        ]);
    }

    /**
     * @param 'google_reviews'|'instagram' $productType
     * @return array{source: 'free'|'classic'|'prepaid', user_subscription_id: int|null, user_prepaid_balance_id: int|null}|null
     */
    private function resolveSource(User $user, string $productType): ?array
    {
        if ($this->hasPrepaidPermission($user)) {
            $balanceId = $this->findPrepaidBalanceId($user->id, $productType);

            return [
                'source'                  => 'prepaid',
                'user_subscription_id'    => null,
                'user_prepaid_balance_id' => $balanceId,
            ];
        }

        if ($this->hasClassicPermission($user)) {
            $subscription = UserSubscriptionModel::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'past_due'])
                ->first();

            if ($subscription === null) {
                return null;
            }

            return [
                'source'                  => 'classic',
                'user_subscription_id'    => (int) $subscription->id,
                'user_prepaid_balance_id' => null,
            ];
        }

        if ($user->can(DomainPermissions::AI_FREE_PREVIEW)) {
            return [
                'source'                  => 'free',
                'user_subscription_id'    => null,
                'user_prepaid_balance_id' => null,
            ];
        }

        return null;
    }

    private function hasPrepaidPermission(User $user): bool
    {
        return $user->can(DomainPermissions::AI_PREPAID_STARTER)
            || $user->can(DomainPermissions::AI_PREPAID_GROWTH)
            || $user->can(DomainPermissions::AI_PREPAID_PRO);
    }

    private function hasClassicPermission(User $user): bool
    {
        return $user->can(DomainPermissions::AI_STANDARD_MONTHLY)
            || $user->can(DomainPermissions::AI_STANDARD_YEARLY)
            || $user->can(DomainPermissions::AI_BASIC_MONTHLY)
            || $user->can(DomainPermissions::AI_BASIC_YEARLY);
    }

    /** @param 'google_reviews'|'instagram' $productType */
    private function findPrepaidBalanceId(int $userId, string $productType): ?int
    {
        $column = $productType === 'google_reviews'
            ? 'google_review_requests_remaining'
            : 'instagram_requests_remaining';

        $balance = UserPrepaidBalanceModel::query()
            ->where('user_id', $userId)
            ->where($column, '>', 0)
            ->orderBy('purchased_at', 'asc')
            ->first();

        return $balance === null ? null : (int) $balance->id;
    }
}
