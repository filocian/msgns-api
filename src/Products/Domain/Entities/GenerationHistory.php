<?php

declare(strict_types=1);

namespace Src\Products\Domain\Entities;

use DateTimeImmutable;
use DateTimeZone;
use Src\Products\Domain\DataTransfer\GenerationHistorySummaryItem;

final class GenerationHistory
{
    /**
     * @param list<GenerationHistorySummaryItem> $summary
     */
    private function __construct(
        public readonly int $id,
        public readonly DateTimeImmutable $generatedAt,
        public readonly int $totalCount,
        public readonly array $summary,
        public readonly string $excelBlob,
        public readonly ?int $generatedById,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @param list<GenerationHistorySummaryItem> $summary
     */
    public static function create(
        DateTimeImmutable $generatedAt,
        int $totalCount,
        array $summary,
        string $excelBlob,
        ?int $generatedById,
    ): self {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return new self(
            id: 0,
            generatedAt: $generatedAt,
            totalCount: $totalCount,
            summary: $summary,
            excelBlob: $excelBlob,
            generatedById: $generatedById,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * @param list<array{type_code: string, type_name: string, quantity: int, size: ?string, description: ?string}> $summaryData
     */
    public static function fromPersistence(
        int $id,
        DateTimeImmutable $generatedAt,
        int $totalCount,
        array $summaryData,
        string $excelBlob,
        ?int $generatedById,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            generatedAt: $generatedAt,
            totalCount: $totalCount,
            summary: array_map(
                static fn (array $item): GenerationHistorySummaryItem => GenerationHistorySummaryItem::fromArray($item),
                $summaryData,
            ),
            excelBlob: $excelBlob,
            generatedById: $generatedById,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @return list<array{type_code: string, type_name: string, quantity: int, size: ?string, description: ?string}>
     */
    public function summaryToArray(): array
    {
        return array_map(
            static fn (GenerationHistorySummaryItem $item): array => $item->toArray(),
            $this->summary,
        );
    }
}
