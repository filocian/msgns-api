<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $source
 * @property int|null $user_subscription_id
 * @property int|null $user_prepaid_balance_id
 * @property \Illuminate\Support\Carbon $used_at
 * @property int|null $tokens_used
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class AiUsageRecordModel extends Model
{
    protected $table = 'ai_usage_records';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'source',
        'user_subscription_id',
        'user_prepaid_balance_id',
        'used_at',
        'tokens_used',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'used_at' => 'datetime',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<UserSubscriptionModel, self> */
    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscriptionModel::class, 'user_subscription_id');
    }

    /** @return BelongsTo<UserPrepaidBalanceModel, self> */
    public function userPrepaidBalance(): BelongsTo
    {
        return $this->belongsTo(UserPrepaidBalanceModel::class, 'user_prepaid_balance_id');
    }
}
