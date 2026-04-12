<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Resources;

use OpenApi\Attributes as OA;
use Src\Subscriptions\Domain\Entities\SubscriptionType;

#[OA\Schema(
    schema: 'SubscriptionTypeResource',
    title: 'SubscriptionTypeResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Google Review Basic'),
        new OA\Property(property: 'slug', type: 'string', example: 'google-review-basic'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Basic plan for Google Reviews'),
        new OA\Property(property: 'mode', type: 'string', enum: ['classic', 'prepaid'], example: 'classic'),
        new OA\Property(property: 'billingPeriods', type: 'array', nullable: true, items: new OA\Items(type: 'string', enum: ['monthly', 'annual'])),
        new OA\Property(property: 'basePriceCents', type: 'integer', example: 200),
        new OA\Property(property: 'permissionName', type: 'string', example: 'ai.google-review-basic'),
        new OA\Property(property: 'googleReviewLimit', type: 'integer', example: 50),
        new OA\Property(property: 'instagramContentLimit', type: 'integer', example: 0),
        new OA\Property(property: 'stripeProductId', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'stripePriceIds', type: 'object', nullable: true, example: null),
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
