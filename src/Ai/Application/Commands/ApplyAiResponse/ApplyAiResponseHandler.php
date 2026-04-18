<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\ApplyAiResponse;

use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\QueuePort;
use Src\Shared\Core\Ports\TransactionPort;

final class ApplyAiResponseHandler implements CommandHandler
{
    public function __construct(
        private readonly AiResponseApplierPort $applier,
        private readonly TransactionPort $transaction,
        private readonly QueuePort $queue,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof ApplyAiResponseCommand);

        $record = AiResponseRecordModel::where('id', $command->id)
            ->where('user_id', $command->userId)
            ->first();

        if ($record === null) {
            throw NotFound::entity('ai_response', $command->id);
        }

        if ($record->product_type === AiProductType::INSTAGRAM_CONTENT->value) {
            $this->dispatchAsyncPublish($record);

            return null;
        }

        $this->applySync($record);

        return null;
    }

    /**
     * Synchronous path — used by low-latency appliers (Google Reviews).
     * Transaction ensures that if the applier throws, the status rolls back to APPROVED.
     */
    private function applySync(AiResponseRecordModel $record): void
    {
        $this->transaction->run(function () use ($record): void {
            $status    = AiResponseStatus::from($record->status);
            $newStatus = $status->transitionTo(AiResponseStatus::from(AiResponseStatus::APPLIED));

            $record->status     = $newStatus->value;
            $record->applied_at = now();
            $record->save();

            // If this throws, the entire transaction rolls back
            $this->applier->apply($record->toDto());
        });
    }

    /**
     * Asynchronous path — used by high-latency appliers (Instagram Graph API publishing).
     * Transitions APPROVED → APPLYING, commits, then hands off to PublishInstagramContentJob
     * so the blocking Graph API polling (~30s) does not occupy the PHP-FPM request thread.
     *
     * Dispatch happens AFTER the transaction commits so the job always observes the committed
     * APPLYING state — this matters in sync queue tests (same connection, would otherwise read
     * uncommitted state) and in real queue workers (different connection, cannot read anyway).
     */
    private function dispatchAsyncPublish(AiResponseRecordModel $record): void
    {
        $this->transaction->run(function () use ($record): void {
            $status    = AiResponseStatus::from($record->status);
            $newStatus = $status->transitionTo(AiResponseStatus::from(AiResponseStatus::APPLYING));

            $record->status = $newStatus->value;
            $record->save();
        });

        $this->queue->dispatch('instagram.publish', [
            'recordId' => $record->id,
        ]);
    }
}
