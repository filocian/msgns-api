<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Persistence;

use Src\Shared\Core\Bus\PaginatedResult;
use Src\Subscriptions\Domain\Entities\SubscriptionType;
use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;
use Src\Subscriptions\Domain\ValueObjects\BillingPeriod;

final class EloquentSubscriptionTypeRepository implements SubscriptionTypeRepositoryPort
{
    public function save(SubscriptionType $subscriptionType): SubscriptionType
    {
        if ($subscriptionType->id === 0) {
            $model = SubscriptionTypeModel::create([
                'name'                    => $subscriptionType->name,
                'slug'                    => $subscriptionType->slug,
                'description'             => $subscriptionType->description,
                'mode'                    => $subscriptionType->mode->value,
                'billing_periods'         => $subscriptionType->billingPeriods !== null
                    ? array_map(static fn (BillingPeriod $bp): string => $bp->value, $subscriptionType->billingPeriods)
                    : null,
                'base_price_cents'        => $subscriptionType->basePriceCents,
                'permission_name'         => $subscriptionType->permissionName,
                'google_review_limit'     => $subscriptionType->googleReviewLimit,
                'instagram_content_limit' => $subscriptionType->instagramContentLimit,
                'stripe_product_id'       => $subscriptionType->stripeProductId,
                'stripe_price_ids'        => $subscriptionType->stripePriceIds,
                'is_active'               => $subscriptionType->isActive,
            ]);

            return $model->toDomainEntity();
        }

        $model = SubscriptionTypeModel::findOrFail($subscriptionType->id);
        $model->forceFill([
            'name'                    => $subscriptionType->name,
            'slug'                    => $subscriptionType->slug,
            'description'             => $subscriptionType->description,
            'mode'                    => $subscriptionType->mode->value,
            'billing_periods'         => $subscriptionType->billingPeriods !== null
                ? array_map(static fn (BillingPeriod $bp): string => $bp->value, $subscriptionType->billingPeriods)
                : null,
            'base_price_cents'        => $subscriptionType->basePriceCents,
            'permission_name'         => $subscriptionType->permissionName,
            'google_review_limit'     => $subscriptionType->googleReviewLimit,
            'instagram_content_limit' => $subscriptionType->instagramContentLimit,
            'stripe_product_id'       => $subscriptionType->stripeProductId,
            'stripe_price_ids'        => $subscriptionType->stripePriceIds,
            'is_active'               => $subscriptionType->isActive,
        ])->save();
        $model->refresh();

        return $model->toDomainEntity();
    }

    public function findById(int $id): ?SubscriptionType
    {
        $model = SubscriptionTypeModel::find($id);

        return $model instanceof SubscriptionTypeModel ? $model->toDomainEntity() : null;
    }

    public function listAdmin(
        int $page,
        int $perPage,
        string $sortBy,
        string $sortDir,
        ?string $mode,
        ?bool $isActive,
    ): PaginatedResult {
        $paginated = SubscriptionTypeModel::query()
            ->when($mode !== null, static fn ($q) => $q->where('mode', $mode))
            ->when($isActive !== null, static fn ($q) => $q->where('is_active', $isActive))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage, ['*'], 'page', $page);

        $items = array_map(
            static fn (SubscriptionTypeModel $model): SubscriptionType => $model->toDomainEntity(),
            $paginated->items(),
        );

        return new PaginatedResult(
            items: $items,
            currentPage: $paginated->currentPage(),
            perPage: $paginated->perPage(),
            total: $paginated->total(),
            lastPage: $paginated->lastPage(),
        );
    }

    /** @return list<SubscriptionType> */
    public function listPublicActive(): array
    {
        $models = SubscriptionTypeModel::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return array_values(array_map(
            static fn (SubscriptionTypeModel $model): SubscriptionType => $model->toDomainEntity(),
            $models->all(),
        ));
    }

    public function hasActiveSubscriptions(int $subscriptionTypeId): bool
    {
        // TODO: implement when user_subscriptions table exists
        return false;
    }

    public function softDelete(int $id): void
    {
        SubscriptionTypeModel::findOrFail($id)->delete();
    }

    public function existsByStripeProductId(string $stripeProductId): bool
    {
        return SubscriptionTypeModel::query()
            ->where('stripe_product_id', $stripeProductId)
            ->exists();
    }
}
