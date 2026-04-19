<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CONSUMPTION ORDER: BE-12/BE-13 deduct from balance rows in FIFO order by purchased_at (oldest first), per feature.
 *
 * @property int $id
 * @property int $user_id
 * @property int $prepaid_package_id
 * @property int $google_review_requests_remaining
 * @property int $instagram_requests_remaining
 * @property \Illuminate\Support\Carbon $purchased_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string $stripe_payment_intent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read PrepaidPackageModel $package
 *
 * @method static Builder<static> withAvailableGoogleReviewRequests()
 * @method static Builder<static> withAvailableInstagramRequests()
 */
final class UserPrepaidBalanceModel extends Model
{
    protected $table = 'user_prepaid_balances';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'prepaid_package_id',
        'google_review_requests_remaining',
        'instagram_requests_remaining',
        'purchased_at',
        'expires_at',
        'stripe_payment_intent_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'purchased_at'                     => 'datetime',
        'expires_at'                       => 'datetime',
        'google_review_requests_remaining' => 'integer',
        'instagram_requests_remaining'     => 'integer',
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
    public function scopeWithAvailableGoogleReviewRequests(Builder $query): Builder
    {
        return $query->where('google_review_requests_remaining', '>', 0);
    }

    /** @param Builder<static> $query */
    public function scopeWithAvailableInstagramRequests(Builder $query): Builder
    {
        return $query->where('instagram_requests_remaining', '>', 0);
    }
}
