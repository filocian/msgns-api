<?php

declare(strict_types=1);

use Src\Ai\Domain\DataTransferObjects\AiResponseRecord as AiResponseRecordDto;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;

describe('AiResponseRecordModel::toDto()', function (): void {

    it('maps all model attributes to the DTO', function (): void {
        $model                         = new AiResponseRecordModel();
        $model->id                     = 'uuid-abc';
        $model->user_id                = 42;
        $model->product_type           = 'google_review';
        $model->product_id             = 7;
        $model->ai_content             = 'AI generated text';
        $model->edited_content         = 'edited version';
        $model->status                 = 'approved';
        $model->setRawAttributes(array_merge($model->getAttributes(), [
            'metadata'   => json_encode(['review_id' => 'rev-abc']),
            'created_at' => '2026-04-17 10:00:00',
        ]), true);

        $dto = $model->toDto();

        expect($dto)->toBeInstanceOf(AiResponseRecordDto::class)
            ->and($dto->id)->toBe('uuid-abc')
            ->and($dto->userId)->toBe(42)
            ->and($dto->productType)->toBe('google_review')
            ->and($dto->productId)->toBe(7)
            ->and($dto->aiContent)->toBe('AI generated text')
            ->and($dto->editedContent)->toBe('edited version')
            ->and($dto->status)->toBe('approved')
            ->and($dto->metadata)->toBe(['review_id' => 'rev-abc']);
    });

    it('maps null metadata to empty array', function (): void {
        $model                 = new AiResponseRecordModel();
        $model->id             = 'uuid-1';
        $model->user_id        = 1;
        $model->product_type   = 'google_review';
        $model->product_id     = 1;
        $model->ai_content     = 'content';
        $model->edited_content = null;
        $model->status         = 'pending';

        $dto = $model->toDto();

        expect($dto->metadata)->toBe([])
            ->and($dto->editedContent)->toBeNull();
    });
});
