<?php

declare(strict_types=1);

namespace Src\Subscriptions\Domain\Entities;

use DateTimeImmutable;
use Src\Subscriptions\Domain\ValueObjects\BillingPeriod;
use Src\Subscriptions\Domain\ValueObjects\SubscriptionMode;

final class SubscriptionType
{
    /**
     * @param list<BillingPeriod>|null $billingPeriods
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

    /** @param list<BillingPeriod>|null $billingPeriods */
    public static function create(
        string $name,
        string $slug,
        ?string $description,
        SubscriptionMode $mode,
        ?array $billingPeriods,
        int $basePriceCents,
        string $permissionName,
        int $googleReviewLimit,
        int $instagramContentLimit,
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
            stripeProductId: null,
            stripePriceIds: null,
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * @param list<BillingPeriod>|null $billingPeriods
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

    /** @param list<BillingPeriod>|null $billingPeriods */
    public function applyUpdate(
        ?string $name,
        ?string $slug,
        ?string $description,
        ?SubscriptionMode $mode,
        ?array $billingPeriods,
        ?int $basePriceCents,
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
        if ($mode !== null) {
            $this->mode = $mode;
        }
        // billingPeriods: always assigned unconditionally — caller (handler) is responsible.
        $this->billingPeriods = $billingPeriods;
        if ($basePriceCents !== null) {
            $this->basePriceCents = $basePriceCents;
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
