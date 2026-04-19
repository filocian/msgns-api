<?php

declare(strict_types=1);

use App\Models\User;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;

describe('ResetExpiredAiResponsesCommand (ai:expire-responses)', function (): void {

    function createAiResponse(int $userId, string $status, bool $expired = false): AiResponseRecordModel
    {
        return AiResponseRecordModel::create([
            'user_id'               => $userId,
            'product_type'          => 'google_review',
            'product_id'            => 1,
            'ai_content'            => 'Test AI content',
            'status'                => $status,
            'system_prompt_snapshot' => 'Test system prompt',
            'expires_at'            => $expired ? now()->subDay() : now()->addDays(5),
        ]);
    }

    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    it('marks pending rows past expires_at as expired', function (): void {
        $record = createAiResponse($this->user->id, AiResponseStatus::PENDING, expired: true);

        $this->artisan('ai:expire-responses')->assertExitCode(0);

        expect($record->fresh()->status)->toBe(AiResponseStatus::EXPIRED);
    });

    it('marks approved rows past expires_at as expired', function (): void {
        $record = createAiResponse($this->user->id, AiResponseStatus::APPROVED, expired: true);

        $this->artisan('ai:expire-responses')->assertExitCode(0);

        expect($record->fresh()->status)->toBe(AiResponseStatus::EXPIRED);
    });

    it('marks edited rows past expires_at as expired', function (): void {
        $record = createAiResponse($this->user->id, AiResponseStatus::EDITED, expired: true);

        $this->artisan('ai:expire-responses')->assertExitCode(0);

        expect($record->fresh()->status)->toBe(AiResponseStatus::EXPIRED);
    });

    it('does not touch rejected rows', function (): void {
        $record = createAiResponse($this->user->id, AiResponseStatus::REJECTED, expired: true);

        $this->artisan('ai:expire-responses')->assertExitCode(0);

        expect($record->fresh()->status)->toBe(AiResponseStatus::REJECTED);
    });

    it('does not touch applied rows', function (): void {
        $record = createAiResponse($this->user->id, AiResponseStatus::APPLIED, expired: true);

        $this->artisan('ai:expire-responses')->assertExitCode(0);

        expect($record->fresh()->status)->toBe(AiResponseStatus::APPLIED);
    });

    it('does not touch already-expired rows', function (): void {
        $record = createAiResponse($this->user->id, AiResponseStatus::EXPIRED, expired: true);

        $this->artisan('ai:expire-responses')->assertExitCode(0);

        // Remains expired (not re-processed)
        expect($record->fresh()->status)->toBe(AiResponseStatus::EXPIRED);
    });

    it('does not touch rows where expires_at is in the future', function (): void {
        $record = createAiResponse($this->user->id, AiResponseStatus::PENDING, expired: false);

        $this->artisan('ai:expire-responses')->assertExitCode(0);

        expect($record->fresh()->status)->toBe(AiResponseStatus::PENDING);
    });

    it('outputs the count of expired records', function (): void {
        createAiResponse($this->user->id, AiResponseStatus::PENDING, expired: true);
        createAiResponse($this->user->id, AiResponseStatus::APPROVED, expired: true);

        $this->artisan('ai:expire-responses')
            ->expectsOutput('Expired 2 AI response(s).')
            ->assertExitCode(0);
    });

    it('outputs zero when no records need expiring', function (): void {
        $this->artisan('ai:expire-responses')
            ->expectsOutput('Expired 0 AI response(s).')
            ->assertExitCode(0);
    });
});
