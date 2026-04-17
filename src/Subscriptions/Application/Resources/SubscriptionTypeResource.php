<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Resources;

use OpenApi\Attributes as OA;
use Src\Subscriptions\Domain\Entities\SubscriptionType;

#[OA\Schema(
    schema: 'SubscriptionTypeResource',
    title: 'SubscriptionTypeResource',
    description: 'Admin-facing resource. Fields mode, billingPeriods, basePriceCents, stripePriceIds are derived server-side from the bound Stripe product and are immutable after creation.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Google Review Basic'),
        new OA\Property(property: 'slug', type: 'string', example: 'google-review-basic'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Basic plan for Google Reviews'),
        new OA\Property(property: 'mode', type: 'string', enum: ['classic', 'prepaid'], description: 'Derived from Stripe price type (recurring → classic, one_time → prepaid).', example: 'classic'),
        new OA\Property(property: 'billingPeriods', type: 'array', nullable: true, description: 'Derived from Stripe prices for the bound product.', items: new OA\Items(type: 'string', enum: ['monthly', 'annual', 'one_time'])),
        new OA\Property(property: 'basePriceCents', type: 'integer', description: 'Price in EUR minor units (cents). Derived from the primary Stripe price (monthly for recurring, one_time for prepaid).', example: 2000),
        new OA\Property(property: 'permissionName', type: 'string', example: 'ai.google-review-basic'),
        new OA\Property(property: 'googleReviewLimit', type: 'integer', example: 50),
        new OA\Property(property: 'instagramContentLimit', type: 'integer', example: 0),
        new OA\Property(property: 'stripeProductId', type: 'string', description: 'Stripe product id (prod_*). Immutable binding.', example: 'prod_abc123'),
        new OA\Property(
            property: 'stripePriceIds',
            type: 'object',
            description: 'Map of app-level billing period label to Stripe price id. Keys: monthly | annual | one_time.',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: ['monthly' => 'price_123', 'annual' => 'price_456'],
        ),
        new OA\Property(property: 'isActive', type: 'boolean', example: true),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00+00:00'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00+00:00'),
    ]
)]
final readonly class SubscriptionTypeResource
{
    /**
     * @param list<string>|null $billingPeriods
     * @param array<string, string>|null $stripePriceIds
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $mode,
        public ?array $billingPeriods,
        public int $basePriceCents,
        public string $permissionName,
        public int $googleReviewLimit,
        public int $instagramContentLimit,
        public ?string $stripeProductId,
        public ?array $stripePriceIds,
        public bool $isActive,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(SubscriptionType $st): self
    {
        return new self(
            id: $st->id,
            name: $st->name,
            slug: $st->slug,
            description: $st->description,
            mode: $st->mode->value,
            billingPeriods: $st->billingPeriods !== null
                ? array_map(static fn ($bp) => $bp->value, $st->billingPeriods)
                : null,
            basePriceCents: $st->basePriceCents,
            permissionName: $st->permissionName,
            googleReviewLimit: $st->googleReviewLimit,
            instagramContentLimit: $st->instagramContentLimit,
            stripeProductId: $st->stripeProductId,
            stripePriceIds: $st->stripePriceIds,
            isActive: $st->isActive,
            createdAt: $st->createdAt->format('c'),
            updatedAt: $st->updatedAt->format('c'),
        );
    }
}
