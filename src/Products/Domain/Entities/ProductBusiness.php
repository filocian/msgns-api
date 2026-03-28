<?php

declare(strict_types=1);

namespace Src\Products\Domain\Entities;

use DateTimeImmutable;

final class ProductBusiness
{
    /**
     * @param array<string, mixed> $types
     * @param array<string, mixed>|null $placeTypes
     */
    private function __construct(
        public readonly int $id,
        public int $productId,
        public int $userId,
        public bool $notABusiness,
        public ?string $name,
        public array $types,
        public ?array $placeTypes,
        public ?string $size,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @param array<string, mixed> $types
     * @param array<string, mixed>|null $placeTypes
     */
    public static function create(
        int $productId,
        int $userId,
        bool $notABusiness = false,
        ?string $name = null,
        array $types = [],
        ?array $placeTypes = null,
        ?string $size = null,
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: 0,
            productId: $productId,
            userId: $userId,
            notABusiness: $notABusiness,
            name: $name,
            types: $types,
            placeTypes: $placeTypes,
            size: $size,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * @param array<string, mixed> $types
     * @param array<string, mixed>|null $placeTypes
     */
    public static function fromPersistence(
        int $id,
        int $productId,
        int $userId,
        bool $notABusiness,
        ?string $name,
        array $types,
        ?array $placeTypes,
        ?string $size,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            productId: $productId,
            userId: $userId,
            notABusiness: $notABusiness,
            name: $name,
            types: $types,
            placeTypes: $placeTypes,
            size: $size,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }
}
