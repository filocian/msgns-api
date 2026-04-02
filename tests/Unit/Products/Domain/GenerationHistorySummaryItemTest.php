<?php

declare(strict_types=1);

use Src\Products\Domain\DataTransfer\GenerationHistorySummaryItem;

describe('GenerationHistorySummaryItem', function () {
    it('constructs with all fields', function () {
        $item = new GenerationHistorySummaryItem(
            typeCode: 'QR_BASIC',
            typeName: 'QR Basic',
            quantity: 10,
            size: 'M',
            description: 'Special batch',
        );

        expect($item->typeCode)->toBe('QR_BASIC')
            ->and($item->typeName)->toBe('QR Basic')
            ->and($item->quantity)->toBe(10)
            ->and($item->size)->toBe('M')
            ->and($item->description)->toBe('Special batch');
    });

    it('constructs with nullable fields as null', function () {
        $item = new GenerationHistorySummaryItem(
            typeCode: 'NFC_PRO',
            typeName: 'NFC Pro',
            quantity: 5,
            size: null,
            description: null,
        );

        expect($item->size)->toBeNull()
            ->and($item->description)->toBeNull();
    });

    it('serializes to array with snake case keys', function () {
        $item = new GenerationHistorySummaryItem(
            typeCode: 'QR_BASIC',
            typeName: 'QR Basic',
            quantity: 7,
            size: 'L',
            description: null,
        );

        expect($item->toArray())->toBe([
            'type_code' => 'QR_BASIC',
            'type_name' => 'QR Basic',
            'quantity' => 7,
            'size' => 'L',
            'description' => null,
        ]);
    });

    it('deserializes from array via fromArray', function () {
        $item = GenerationHistorySummaryItem::fromArray([
            'type_code' => 'NFC_PRO',
            'type_name' => 'NFC Pro',
            'quantity' => 3,
            'size' => null,
            'description' => 'Limited',
        ]);

        expect($item->typeCode)->toBe('NFC_PRO')
            ->and($item->typeName)->toBe('NFC Pro')
            ->and($item->quantity)->toBe(3)
            ->and($item->size)->toBeNull()
            ->and($item->description)->toBe('Limited');
    });
});
