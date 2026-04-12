<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Resources;

use Src\Subscriptions\Domain\Entities\SubscriptionType;

final readonly class SubscriptionTypeListItemResource
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $mode,
        public int $basePriceCents,
        public bool $isActive,
        public int $googleReviewLimit,
        public int $instagramContentLimit,
        public string $createdAt,
    ) {}

    public static function fromEntity(SubscriptionType $st): self
    {
        return new self(
            id: $st->id,
            name: $st->name,
            slug: $st->slug,
            mode: $st->mode->value,
            basePriceCents: $st->basePriceCents,
            isActive: $st->isActive,
            googleReviewLimit: $st->googleReviewLimit,
            instagramContentLimit: $st->instagramContentLimit,
            createdAt: $st->createdAt->format('c'),
        );
    }
}
