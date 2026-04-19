<?php

declare(strict_types=1);

namespace Src\Ai\Domain\DataTransferObjects;

use DateTimeImmutable;

/**
 * Immutable snapshot of an AI response record passed to appliers.
 *
 * Pure data — no Eloquent/framework coupling. Built from the Eloquent model
 * via AiResponseRecordModel::toDto() in Application layer.
 *
 * @phpstan-type MetadataArray array<string, mixed>
 */
final readonly class AiResponseRecord
{
    /**
     * @param MetadataArray $metadata
     */
    public function __construct(
        public string $id,
        public int $userId,
        public string $productType,
        public int $productId,
        public string $aiContent,
        public ?string $editedContent,
        public string $status,
        public array $metadata,
        public DateTimeImmutable $createdAt,
    ) {}
}
