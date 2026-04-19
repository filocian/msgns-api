<?php

declare(strict_types=1);

namespace Src\Instagram\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Shared\Core\Ports\LogPort;
use Throwable;

/**
 * Publishes an approved Instagram AiResponse via the composite applier, asynchronously.
 *
 * Dispatched by `ApplyAiResponseHandler` for records with product_type='instagram_content',
 * keeping the blocking Graph API polling (~30s) off the PHP-FPM request thread.
 *
 * Retries are handled by Laravel (`$tries = 3`). If every retry fails, `failed()` rolls the
 * record back to APPROVED so the user can re-hit POST /apply. The APPLYING state is the
 * intermediate state that sits between APPROVED and APPLIED while the job is in-flight.
 */
final class PublishInstagramContentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $recordId,
    ) {}

    public function handle(AiResponseApplierPort $applier, LogPort $log): void
    {
        $record = AiResponseRecordModel::find($this->recordId);

        if ($record === null) {
            $log->warning('instagram.publish_job.record_not_found', [
                'record_id' => $this->recordId,
            ]);

            return;
        }

        // Idempotency: a previous retry may have already transitioned this record.
        if ($record->status === AiResponseStatus::APPLIED) {
            return;
        }

        // Any throw propagates so Laravel retries up to $tries times.
        $applier->apply($record->toDto());

        $record->status     = AiResponseStatus::APPLIED;
        $record->applied_at = now();
        $record->save();
    }

    /**
     * Invoked by Laravel after all retry attempts have failed.
     *
     * Resets the record to APPROVED via direct write (bypassing the state machine — this
     * is a system rollback, not a user-initiated transition). The user can retry by calling
     * POST /api/v2/ai/responses/{id}/apply again.
     */
    public function failed(Throwable $e): void
    {
        $record = AiResponseRecordModel::find($this->recordId);

        if ($record === null || $record->status !== AiResponseStatus::APPLYING) {
            return;
        }

        $record->status     = AiResponseStatus::APPROVED;
        $record->applied_at = null;
        $record->save();

        /** @var LogPort $log */
        $log = app(LogPort::class);
        $log->warning('instagram.publish_job.retries_exhausted', [
            'record_id' => $this->recordId,
            'reason'    => $e->getMessage(),
        ]);
    }
}
