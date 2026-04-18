<?php

declare(strict_types=1);

use App\Models\User;
use Src\Ai\Application\Commands\ApplyAiResponse\ApplyAiResponseCommand;
use Src\Ai\Application\Commands\ApplyAiResponse\ApplyAiResponseHandler;
use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\QueuePort;
use Src\Shared\Core\Ports\TransactionPort;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

afterEach(fn () => Mockery::close());

function makeApprovedRecord(User $user, string $productType): AiResponseRecordModel
{
    return AiResponseRecordModel::create([
        'id'                     => (string) \Illuminate\Support\Str::uuid(),
        'user_id'                => $user->id,
        'product_type'           => $productType,
        'product_id'             => 42,
        'ai_content'             => 'Approved content',
        'status'                 => AiResponseStatus::APPROVED,
        'system_prompt_snapshot' => 'default',
        'expires_at'             => now()->addDays(5),
        'metadata'               => ['s3_image_url' => 'https://s3.example.com/img.jpg'],
    ]);
}

function makeTxPassthrough(): TransactionPort
{
    $tx = Mockery::mock(TransactionPort::class);
    $tx->shouldReceive('run')->andReturnUsing(fn (callable $c) => $c());

    return $tx;
}

describe('ApplyAiResponseHandler — Instagram async path', function (): void {

    it('transitions APPROVED → APPLYING and dispatches instagram.publish job without calling the applier', function (): void {
        $user   = User::factory()->create();
        $record = makeApprovedRecord($user, AiProductType::INSTAGRAM_CONTENT->value);

        $applier = Mockery::mock(AiResponseApplierPort::class);
        $applier->shouldNotReceive('apply');

        $queue = Mockery::mock(QueuePort::class);
        $queue->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn (string $name, array $payload): bool => $name === 'instagram.publish'
                && ($payload['recordId'] ?? null) === $record->id);

        $handler = new ApplyAiResponseHandler($applier, makeTxPassthrough(), $queue);
        $handler->handle(new ApplyAiResponseCommand(id: $record->id, userId: $user->id));

        $record->refresh();
        expect($record->status)->toBe(AiResponseStatus::APPLYING)
            ->and($record->applied_at)->toBeNull();
    });
});

describe('ApplyAiResponseHandler — Google Reviews sync path (unchanged)', function (): void {

    it('transitions APPROVED → APPLIED and invokes the applier synchronously', function (): void {
        $user   = User::factory()->create();
        $record = makeApprovedRecord($user, AiProductType::GOOGLE_REVIEW->value);

        $applier = Mockery::mock(AiResponseApplierPort::class);
        $applier->shouldReceive('apply')->once();

        $queue = Mockery::mock(QueuePort::class);
        $queue->shouldNotReceive('dispatch');

        $handler = new ApplyAiResponseHandler($applier, makeTxPassthrough(), $queue);
        $handler->handle(new ApplyAiResponseCommand(id: $record->id, userId: $user->id));

        $record->refresh();
        expect($record->status)->toBe(AiResponseStatus::APPLIED)
            ->and($record->applied_at)->not()->toBeNull();
    });
});

describe('ApplyAiResponseHandler — failure paths', function (): void {

    it('throws NotFound when the record does not exist', function (): void {
        $user = User::factory()->create();

        $applier = Mockery::mock(AiResponseApplierPort::class);
        $queue   = Mockery::mock(QueuePort::class);

        $handler = new ApplyAiResponseHandler($applier, makeTxPassthrough(), $queue);

        expect(fn () => $handler->handle(new ApplyAiResponseCommand(
            id: '00000000-0000-0000-0000-000000000099',
            userId: $user->id,
        )))->toThrow(NotFound::class);
    });

    it('throws ValidationFailed when trying to apply a pending record', function (): void {
        $user = User::factory()->create();

        $record = AiResponseRecordModel::create([
            'id'                     => (string) \Illuminate\Support\Str::uuid(),
            'user_id'                => $user->id,
            'product_type'           => AiProductType::INSTAGRAM_CONTENT->value,
            'product_id'             => 42,
            'ai_content'             => 'Pending',
            'status'                 => AiResponseStatus::PENDING,
            'system_prompt_snapshot' => 'default',
            'expires_at'             => now()->addDays(5),
            'metadata'               => [],
        ]);

        $applier = Mockery::mock(AiResponseApplierPort::class);
        $queue   = Mockery::mock(QueuePort::class);

        $handler = new ApplyAiResponseHandler($applier, makeTxPassthrough(), $queue);

        expect(fn () => $handler->handle(new ApplyAiResponseCommand(
            id: $record->id,
            userId: $user->id,
        )))->toThrow(ValidationFailed::class);
    });
});
