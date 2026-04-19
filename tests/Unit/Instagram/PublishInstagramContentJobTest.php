<?php

declare(strict_types=1);

use App\Models\User;
use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Instagram\Application\Jobs\PublishInstagramContentJob;
use Src\Shared\Core\Ports\LogPort;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

afterEach(fn () => Mockery::close());

function makeApplyingInstagramRecord(User $user): AiResponseRecordModel
{
    return AiResponseRecordModel::create([
        'id'                     => (string) \Illuminate\Support\Str::uuid(),
        'user_id'                => $user->id,
        'product_type'           => AiProductType::INSTAGRAM_CONTENT->value,
        'product_id'             => 42,
        'ai_content'             => 'Draft caption',
        'status'                 => AiResponseStatus::APPLYING,
        'system_prompt_snapshot' => 'default',
        'expires_at'             => now()->addDays(5),
        'metadata'               => ['s3_image_url' => 'https://s3.example.com/img.jpg'],
    ]);
}

describe('PublishInstagramContentJob::handle', function (): void {

    it('calls the applier and transitions APPLYING → APPLIED on success', function (): void {
        $user   = User::factory()->create();
        $record = makeApplyingInstagramRecord($user);

        $applier = Mockery::mock(AiResponseApplierPort::class);
        $applier->shouldReceive('apply')->once();

        $log = Mockery::mock(LogPort::class);
        $log->shouldReceive('info')->zeroOrMoreTimes();

        $job = new PublishInstagramContentJob(recordId: $record->id);
        $job->handle($applier, $log);

        $record->refresh();
        expect($record->status)->toBe(AiResponseStatus::APPLIED)
            ->and($record->applied_at)->not()->toBeNull();
    });

    it('does nothing when the record has already transitioned to APPLIED (idempotency)', function (): void {
        $user = User::factory()->create();

        $record = AiResponseRecordModel::create([
            'id'                     => (string) \Illuminate\Support\Str::uuid(),
            'user_id'                => $user->id,
            'product_type'           => AiProductType::INSTAGRAM_CONTENT->value,
            'product_id'             => 42,
            'ai_content'             => 'Draft',
            'status'                 => AiResponseStatus::APPLIED,
            'applied_at'             => now()->subMinute(),
            'system_prompt_snapshot' => 'default',
            'expires_at'             => now()->addDays(5),
            'metadata'               => ['s3_image_url' => 'https://s3.example.com/img.jpg'],
        ]);

        $applier = Mockery::mock(AiResponseApplierPort::class);
        $applier->shouldNotReceive('apply');

        $log = Mockery::mock(LogPort::class);
        $log->shouldReceive('info')->zeroOrMoreTimes();

        $job = new PublishInstagramContentJob(recordId: $record->id);
        $job->handle($applier, $log);

        $record->refresh();
        expect($record->status)->toBe(AiResponseStatus::APPLIED);
    });

    it('logs a warning and returns when the record is not found', function (): void {
        $applier = Mockery::mock(AiResponseApplierPort::class);
        $applier->shouldNotReceive('apply');

        $log = Mockery::mock(LogPort::class);
        $log->shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $msg, array $ctx): bool => $msg === 'instagram.publish_job.record_not_found'
                && ($ctx['record_id'] ?? null) === '00000000-0000-0000-0000-000000000099');

        $job = new PublishInstagramContentJob(recordId: '00000000-0000-0000-0000-000000000099');
        $job->handle($applier, $log);
    });

    it('propagates applier exceptions so Laravel can retry', function (): void {
        $user   = User::factory()->create();
        $record = makeApplyingInstagramRecord($user);

        $applier = Mockery::mock(AiResponseApplierPort::class);
        $applier->shouldReceive('apply')->andThrow(new RuntimeException('Graph API timeout'));

        $log = Mockery::mock(LogPort::class);
        $log->shouldReceive('info')->zeroOrMoreTimes();

        $job = new PublishInstagramContentJob(recordId: $record->id);

        expect(fn () => $job->handle($applier, $log))->toThrow(RuntimeException::class);

        $record->refresh();
        expect($record->status)->toBe(AiResponseStatus::APPLYING); // state preserved for retry
    });
});

describe('PublishInstagramContentJob::failed', function (): void {

    it('rolls APPLYING back to APPROVED after retries are exhausted', function (): void {
        $user   = User::factory()->create();
        $record = makeApplyingInstagramRecord($user);

        $job = new PublishInstagramContentJob(recordId: $record->id);
        $job->failed(new RuntimeException('all retries exhausted'));

        $record->refresh();
        expect($record->status)->toBe(AiResponseStatus::APPROVED)
            ->and($record->applied_at)->toBeNull();
    });

    it('is a no-op if the record has advanced past APPLYING (e.g. a late retry succeeded)', function (): void {
        $user = User::factory()->create();

        $record = AiResponseRecordModel::create([
            'id'                     => (string) \Illuminate\Support\Str::uuid(),
            'user_id'                => $user->id,
            'product_type'           => AiProductType::INSTAGRAM_CONTENT->value,
            'product_id'             => 42,
            'ai_content'             => 'Draft',
            'status'                 => AiResponseStatus::APPLIED,
            'applied_at'             => now()->subMinute(),
            'system_prompt_snapshot' => 'default',
            'expires_at'             => now()->addDays(5),
            'metadata'               => ['s3_image_url' => 'https://s3.example.com/img.jpg'],
        ]);

        $job = new PublishInstagramContentJob(recordId: $record->id);
        $job->failed(new RuntimeException('late failure'));

        $record->refresh();
        expect($record->status)->toBe(AiResponseStatus::APPLIED); // unchanged
    });

    it('is a no-op if the record no longer exists', function (): void {
        $job = new PublishInstagramContentJob(recordId: '00000000-0000-0000-0000-000000000099');

        // should not throw
        $job->failed(new RuntimeException('missing'));

        expect(true)->toBeTrue();
    });
});
