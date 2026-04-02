<?php

declare(strict_types=1);

use Src\Products\Domain\DataTransfer\GenerationHistorySummaryItem;
use Src\Products\Domain\Entities\GenerationHistory;

describe('GenerationHistory Entity', function () {
    it('creates a GenerationHistory entity via create with correct defaults', function () {
        $generatedAt = new DateTimeImmutable('2026-04-02T12:30:00+00:00');

        $history = GenerationHistory::create(
            generatedAt: $generatedAt,
            totalCount: 15,
            summary: [
                new GenerationHistorySummaryItem('QR_BASIC', 'QR Basic', 10, null, null),
            ],
            excelBlob: "PK\x03\x04binary",
            generatedById: 9,
        );

        expect($history->id)->toBe(0)
            ->and($history->generatedAt)->toBe($generatedAt)
            ->and($history->totalCount)->toBe(15)
            ->and($history->summary)->toHaveCount(1)
            ->and($history->summary[0])->toBeInstanceOf(GenerationHistorySummaryItem::class)
            ->and($history->generatedById)->toBe(9)
            ->and($history->createdAt->getTimezone()->getName())->toBe('UTC')
            ->and($history->updatedAt->getTimezone()->getName())->toBe('UTC');
    });

    it('reconstitutes from persistence via fromPersistence mapping summaryData to summary item objects', function () {
        $createdAt = new DateTimeImmutable('2026-04-02T12:30:01+00:00');
        $updatedAt = new DateTimeImmutable('2026-04-02T12:30:02+00:00');

        $history = GenerationHistory::fromPersistence(
            id: 42,
            generatedAt: new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
            totalCount: 20,
            summaryData: [
                [
                    'type_code' => 'NFC_PRO',
                    'type_name' => 'NFC Pro',
                    'quantity' => 8,
                    'size' => 'M',
                    'description' => 'Special batch',
                ],
            ],
            excelBlob: "PK\x03\x04stored",
            generatedById: null,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        expect($history->id)->toBe(42)
            ->and($history->summary[0])->toBeInstanceOf(GenerationHistorySummaryItem::class)
            ->and($history->summary[0]->typeCode)->toBe('NFC_PRO')
            ->and($history->generatedById)->toBeNull()
            ->and($history->createdAt)->toBe($createdAt)
            ->and($history->updatedAt)->toBe($updatedAt);
    });

    it('serializes summary to array correctly via summaryToArray', function () {
        $history = GenerationHistory::create(
            generatedAt: new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
            totalCount: 12,
            summary: [
                new GenerationHistorySummaryItem('QR_BASIC', 'QR Basic', 10, null, null),
                new GenerationHistorySummaryItem('NFC_PRO', 'NFC Pro', 2, 'S', 'Limited'),
            ],
            excelBlob: 'blob',
            generatedById: 1,
        );

        expect($history->summaryToArray())->toBe([
            [
                'type_code' => 'QR_BASIC',
                'type_name' => 'QR Basic',
                'quantity' => 10,
                'size' => null,
                'description' => null,
            ],
            [
                'type_code' => 'NFC_PRO',
                'type_name' => 'NFC Pro',
                'quantity' => 2,
                'size' => 'S',
                'description' => 'Limited',
            ],
        ]);
    });

    it('handles empty summary array', function () {
        $history = GenerationHistory::create(
            generatedAt: new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
            totalCount: 0,
            summary: [],
            excelBlob: 'blob',
            generatedById: null,
        );

        expect($history->summary)->toBe([])
            ->and($history->summaryToArray())->toBe([]);
    });

    it('stores excelBlob as raw binary string', function () {
        $binary = "PK\x03\x04\x14\x00\x06\x00";

        $history = GenerationHistory::create(
            generatedAt: new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
            totalCount: 1,
            summary: [],
            excelBlob: $binary,
            generatedById: null,
        );

        expect($history->excelBlob)->toBe($binary);
    });
});
