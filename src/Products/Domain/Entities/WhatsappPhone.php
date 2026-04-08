<?php

declare(strict_types=1);

namespace Src\Products\Domain\Entities;

use DateTimeImmutable;

final class WhatsappPhone
{
    private function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly string $phone,
        public readonly string $prefix,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        int $productId,
        string $phone,
        string $prefix,
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: 0,
            productId: $productId,
            phone: $phone,
            prefix: $prefix,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromPersistence(
        int $id,
        int $productId,
        string $phone,
        string $prefix,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            productId: $productId,
            phone: $phone,
            prefix: $prefix,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }
}
