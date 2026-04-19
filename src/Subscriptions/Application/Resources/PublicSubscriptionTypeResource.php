<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Resources;

use OpenApi\Attributes as OA;
use Src\Subscriptions\Domain\Entities\SubscriptionType;

#[OA\Schema(
    schema: 'PublicSubscriptionTypeResource',
    title: 'PublicSubscriptionTypeResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Google Review Basic'),
        new OA\Property(property: 'slug', type: 'string', example: 'google-review-basic'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Basic plan for Google Reviews'),
        new OA\Property(property: 'mode', type: 'string', enum: ['classic', 'prepaid'], example: 'classic'),
        new OA\Property(property: 'billingPeriods', type: 'array', nullable: true, items: new OA\Items(type: 'string', enum: ['monthly', 'annual'])),
        new OA\Property(property: 'basePriceCents', type: 'integer', description: 'Price in EUR minor units (cents)', example: 2000),
        new OA\Property(property: 'googleReviewLimit', type: 'integer', example: 50),
        new OA\Property(property: 'instagramContentLimit', type: 'integer', example: 0),
    ]
)]
final readonly class PublicSubscriptionTypeResource
{
    /**
     * @param list<string>|null $billingPeriods
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $mode,
        public ?array $billingPeriods,
        public int $basePriceCents,
        public int $googleReviewLimit,
        public int $instagramContentLimit,
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
            googleReviewLimit: $st->googleReviewLimit,
            instagramContentLimit: $st->instagramContentLimit,
        );
    }
}
