<?php

declare(strict_types=1);

use Src\Products\Application\Resources\GenerationHistoryListItemResource;

describe('GenerationHistoryListItemResource', function () {
    it('serializes all fields to array', function () {
        $resource = new GenerationHistoryListItemResource(
            id: 42,
            generatedAt: '2026-04-02T14:30:00+02:00',
            totalCount: 15,
            summary: [[
                'type_code' => 'QR_BASIC',
                'type_name' => 'QR Basic',
                'quantity' => 10,
                'size' => null,
                'description' => null,
            ]],
            generatedBy: ['id' => 7, 'email' => 'admin@example.com'],
        );

        expect($resource->toArray())->toBe([
            'id' => 42,
            'generated_at' => '2026-04-02T14:30:00+02:00',
            'total_count' => 15,
            'summary' => [[
                'type_code' => 'QR_BASIC',
                'type_name' => 'QR Basic',
                'quantity' => 10,
                'size' => null,
                'description' => null,
            ]],
            'generated_by' => ['id' => 7, 'email' => 'admin@example.com'],
        ]);
    });

    it('serializes null generatedBy as null', function () {
        $resource = new GenerationHistoryListItemResource(
            id: 1,
            generatedAt: '2026-04-02T12:30:00+00:00',
            totalCount: 1,
            summary: [],
            generatedBy: null,
        );

        expect($resource->toArray()['generated_by'])->toBeNull();
    });
});
