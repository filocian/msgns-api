<?php

declare(strict_types=1);

namespace Src\Products\Domain\Entities;

use DateTimeImmutable;

final class WhatsappMessage
{
    private function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly int $phoneId,
        public readonly int $localeId,
        public readonly string $localeCode,
        public readonly string $message,
        public bool $isDefault,
        public readonly string $phone,
        public readonly string $prefix,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        int $productId,
        int $phoneId,
        int $localeId,
        string $localeCode,
        string $message,
        bool $isDefault,
        string $phone = '',
        string $prefix = '',
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: 0,
            productId: $productId,
            phoneId: $phoneId,
            localeId: $localeId,
            localeCode: $localeCode,
            message: $message,
            isDefault: $isDefault,
            phone: $phone,
            prefix: $prefix,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromPersistence(
        int $id,
        int $productId,
        int $phoneId,
        int $localeId,
        string $localeCode,
        string $message,
        bool $isDefault,
        string $phone,
        string $prefix,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            productId: $productId,
            phoneId: $phoneId,
            localeId: $localeId,
            localeCode: $localeCode,
            message: $message,
            isDefault: $isDefault,
            phone: $phone,
            prefix: $prefix,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }
}
