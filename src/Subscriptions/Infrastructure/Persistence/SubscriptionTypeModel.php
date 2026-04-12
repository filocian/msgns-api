<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Persistence;

use Database\Factories\SubscriptionTypeModelFactory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Src\Subscriptions\Domain\Entities\SubscriptionType;
use Src\Subscriptions\Domain\ValueObjects\BillingPeriod;
use Src\Subscriptions\Domain\ValueObjects\SubscriptionMode;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $mode
 * @property array|null $billing_periods
 * @property int $base_price_cents
 * @property string $permission_name
 * @property int $google_review_limit
 * @property int $instagram_content_limit
 * @property string|null $stripe_product_id
 * @property array|null $stripe_price_ids
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class SubscriptionTypeModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subscription_types';

    /** @var string */
    protected $keyType = 'int';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'mode',
        'billing_periods',
        'base_price_cents',
        'permission_name',
        'google_review_limit',
        'instagram_content_limit',
        'stripe_product_id',
        'stripe_price_ids',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'billing_periods'  => 'array',
        'stripe_price_ids' => 'array',
        'is_active'        => 'boolean',
    ];

    protected static function newFactory(): SubscriptionTypeModelFactory
    {
        return SubscriptionTypeModelFactory::new();
    }

    public function toDomainEntity(): SubscriptionType
    {
        $rawBillingPeriods = $this->billing_periods;
        if (is_string($rawBillingPeriods)) {
            $rawBillingPeriods = json_decode($rawBillingPeriods, true);
        }

        $billingPeriods = $rawBillingPeriods !== null
            ? array_map(static fn (string $v): BillingPeriod => BillingPeriod::from($v), $rawBillingPeriods)
            : null;

        return SubscriptionType::fromPersistence(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            mode: SubscriptionMode::from($this->mode),
            billingPeriods: $billingPeriods,
            basePriceCents: $this->base_price_cents,
            permissionName: $this->permission_name,
            googleReviewLimit: $this->google_review_limit,
            instagramContentLimit: $this->instagram_content_limit,
            stripeProductId: $this->stripe_product_id,
            stripePriceIds: $this->stripe_price_ids,
            isActive: $this->is_active,
            createdAt: $this->created_at
                ? DateTimeImmutable::createFromInterface($this->created_at)
                : new DateTimeImmutable(),
            updatedAt: $this->updated_at
                ? DateTimeImmutable::createFromInterface($this->updated_at)
                : new DateTimeImmutable(),
        );
    }
}
