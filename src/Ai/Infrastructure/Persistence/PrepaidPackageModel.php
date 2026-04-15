<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $stripe_price_id
 * @property string $permission_name
 * @property int $google_review_limit
 * @property int $instagram_content_limit
 * @property int $price_cents
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserPrepaidBalanceModel> $balances
 *
 * @method static Builder<static> active()
 */
final class PrepaidPackageModel extends Model
{
    protected $table = 'prepaid_packages';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'stripe_price_id',
        'permission_name',
        'google_review_limit',
        'instagram_content_limit',
        'price_cents',
        'active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'active'                  => 'boolean',
        'google_review_limit'     => 'integer',
        'instagram_content_limit' => 'integer',
        'price_cents'             => 'integer',
    ];

    /** @return HasMany<UserPrepaidBalanceModel> */
    public function balances(): HasMany
    {
        return $this->hasMany(UserPrepaidBalanceModel::class, 'prepaid_package_id');
    }

    /** @param Builder<static> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
