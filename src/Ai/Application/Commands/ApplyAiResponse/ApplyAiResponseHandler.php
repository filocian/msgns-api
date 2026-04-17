<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\ApplyAiResponse;

use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

final class ApplyAiResponseHandler implements CommandHandler
{
    public function __construct(
        private readonly AiResponseApplierPort $applier,
        private readonly TransactionPort $transaction,
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

        $this->transaction->run(function () use ($record): void {
            $status    = AiResponseStatus::from($record->status);
            $newStatus = $status->transitionTo(AiResponseStatus::from(AiResponseStatus::APPLIED));

            $record->status     = $newStatus->value;
            $record->applied_at = now();
            $record->save();

            // If this throws, the entire transaction rolls back
            $this->applier->apply($record->toDto());
        });

        return null;
    }
}
