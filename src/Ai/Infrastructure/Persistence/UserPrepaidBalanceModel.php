<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CONSUMPTION ORDER: BE-8 deducts from balance rows in FIFO order by purchased_at (oldest first).
 *
 * @property int $id
 * @property int $user_id
 * @property int $prepaid_package_id
 * @property int $requests_remaining
 * @property \Illuminate\Support\Carbon $purchased_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string $stripe_payment_intent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read PrepaidPackageModel $package
 *
 * @method static Builder<static> withAvailableRequests()
 */
final class UserPrepaidBalanceModel extends Model
{
    protected $table = 'user_prepaid_balances';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'prepaid_package_id',
        'requests_remaining',
        'purchased_at',
        'expires_at',
        'stripe_payment_intent_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'purchased_at'      => 'datetime',
        'expires_at'        => 'datetime',
        'requests_remaining' => 'integer',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<PrepaidPackageModel, self> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(PrepaidPackageModel::class, 'prepaid_package_id');
    }

    /** @param Builder<static> $query */
    public function scopeWithAvailableRequests(Builder $query): Builder
    {
        return $query->where('requests_remaining', '>', 0);
    }
}
