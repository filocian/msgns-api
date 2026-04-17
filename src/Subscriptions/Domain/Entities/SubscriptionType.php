<?php

declare(strict_types=1);

namespace Src\Subscriptions\Domain\Entities;

use DateTimeImmutable;
use Src\Subscriptions\Domain\ValueObjects\BillingPeriod;
use Src\Subscriptions\Domain\ValueObjects\SubscriptionMode;

final class SubscriptionType
{
    /**
     * @param list<BillingPeriod>|null   $billingPeriods
     * @param array<string, string>|null $stripePriceIds
     */
    private function __construct(
        public readonly int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public SubscriptionMode $mode,
        public ?array $billingPeriods,
        public int $basePriceCents,
        public string $permissionName,
        public int $googleReviewLimit,
        public int $instagramContentLimit,
        public ?string $stripeProductId,
        public ?array $stripePriceIds,
        public bool $isActive,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    /**
     * Creates a new SubscriptionType bound to a Stripe product and its derived prices.
     *
     * All Stripe-derived values (mode, billingPeriods, basePriceCents, stripePriceIds)
     * MUST be supplied by the Application handler which is responsible for fetching
     * and validating them against the Stripe catalog. The entity itself performs no
     * external I/O.
     *
     * @param list<BillingPeriod>   $billingPeriods
     * @param array<string, string> $stripePriceIds
     */
    public static function create(
        string $name,
        string $slug,
        ?string $description,
        SubscriptionMode $mode,
        array $billingPeriods,
        int $basePriceCents,
        string $permissionName,
        int $googleReviewLimit,
        int $instagramContentLimit,
        string $stripeProductId,
        array $stripePriceIds,
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: 0,
            name: $name,
            slug: $slug,
            description: $description,
            mode: $mode,
            billingPeriods: $billingPeriods,
            basePriceCents: $basePriceCents,
            permissionName: $permissionName,
            googleReviewLimit: $googleReviewLimit,
            instagramContentLimit: $instagramContentLimit,
            stripeProductId: $stripeProductId,
            stripePriceIds: $stripePriceIds,
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * @param list<BillingPeriod>|null   $billingPeriods
     * @param array<string, string>|null $stripePriceIds
     */
    public static function fromPersistence(
        int $id,
        string $name,
        string $slug,
        ?string $description,
        SubscriptionMode $mode,
        ?array $billingPeriods,
        int $basePriceCents,
        string $permissionName,
        int $googleReviewLimit,
        int $instagramContentLimit,
        ?string $stripeProductId,
        ?array $stripePriceIds,
        bool $isActive,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            mode: $mode,
            billingPeriods: $billingPeriods,
            basePriceCents: $basePriceCents,
            permissionName: $permissionName,
            googleReviewLimit: $googleReviewLimit,
            instagramContentLimit: $instagramContentLimit,
            stripeProductId: $stripeProductId,
            stripePriceIds: $stripePriceIds,
            isActive: $isActive,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * Updates mutable business fields only.
     *
     * Stripe-binding and pricing fields (mode, billingPeriods, basePriceCents,
     * stripeProductId, stripePriceIds) are immutable post-create and intentionally
     * not exposed through this signature. Any attempt to pass them is rejected
     * by the PHP type system at compile time.
     */
    public function applyUpdate(
        ?string $name,
        ?string $slug,
        ?string $description,
        ?string $permissionName,
        ?int $googleReviewLimit,
        ?int $instagramContentLimit,
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($slug !== null) {
            $this->slug = $slug;
        }
        if ($description !== null) {
            $this->description = $description;
        }
        if ($permissionName !== null) {
            $this->permissionName = $permissionName;
        }
        if ($googleReviewLimit !== null) {
            $this->googleReviewLimit = $googleReviewLimit;
        }
        if ($instagramContentLimit !== null) {
            $this->instagramContentLimit = $instagramContentLimit;
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function toggleActive(): void
    {
        $this->isActive = !$this->isActive;
        $this->updatedAt = new DateTimeImmutable();
    }
}
