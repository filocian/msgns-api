<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Persistence;

use App\Models\User;
use Database\Factories\UserSubscriptionModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

/**
 * @property int $id
 * @property int $user_id
 * @property int $subscription_type_id
 * @property string $billing_period
 * @property string $stripe_subscription_id
 * @property string $status
 * @property \Illuminate\Support\Carbon $current_period_start
 * @property \Illuminate\Support\Carbon $current_period_end
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read SubscriptionTypeModel $subscriptionType
 */
final class UserSubscriptionModel extends Model
{
    use HasFactory;

    protected $table = 'user_subscriptions';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'subscription_type_id',
        'billing_period',
        'stripe_subscription_id',
        'status',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end'   => 'datetime',
        'cancelled_at'         => 'datetime',
    ];

    protected static function newFactory(): UserSubscriptionModelFactory
    {
        return UserSubscriptionModelFactory::new();
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<SubscriptionTypeModel, self> */
    public function subscriptionType(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTypeModel::class, 'subscription_type_id');
    }

    /** @return HasMany<AiUsageRecordModel> */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(AiUsageRecordModel::class, 'user_subscription_id');
    }
}
