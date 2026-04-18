<?php

declare(strict_types=1);

use Src\Ai\Domain\DataTransferObjects\AiResponseRecord;

describe('AiResponseRecord DTO', function (): void {

    it('constructs with all nine fields', function (): void {
        $createdAt = new DateTimeImmutable('2026-04-17T10:00:00Z');

        $dto = new AiResponseRecord(
            id: 'uuid-123',
            userId: 42,
            productType: 'google_review',
            productId: 7,
            aiContent: 'AI generated text',
            editedContent: null,
            status: 'pending',
            metadata: ['review_id' => 'rev-abc'],
            createdAt: $createdAt,
        );

        expect($dto->id)->toBe('uuid-123')
            ->and($dto->userId)->toBe(42)
            ->and($dto->productType)->toBe('google_review')
            ->and($dto->productId)->toBe(7)
            ->and($dto->aiContent)->toBe('AI generated text')
            ->and($dto->editedContent)->toBeNull()
            ->and($dto->status)->toBe('pending')
            ->and($dto->metadata)->toBe(['review_id' => 'rev-abc'])
            ->and($dto->createdAt)->toBe($createdAt);
    });

    it('allows edited content', function (): void {
        $dto = new AiResponseRecord(
            id: 'uuid-123',
            userId: 1,
            productType: 'google_review',
            productId: 1,
            aiContent: 'original',
            editedContent: 'edited text',
            status: 'edited',
            metadata: [],
            createdAt: new DateTimeImmutable(),
        );

        expect($dto->editedContent)->toBe('edited text');
    });

    it('is readonly — attempting mutation throws', function (): void {
        $dto = new AiResponseRecord(
            id: 'uuid-123',
            userId: 1,
            productType: 'google_review',
            productId: 1,
            aiContent: 'original',
            editedContent: null,
            status: 'pending',
            metadata: [],
            createdAt: new DateTimeImmutable(),
        );

        expect(fn () => $dto->id = 'mutated')->toThrow(Error::class);
    });
});
