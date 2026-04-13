<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\ApproveAiResponse;

use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecord;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class ApproveAiResponseHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof ApproveAiResponseCommand);

        $record = AiResponseRecord::where('id', $command->id)
            ->where('user_id', $command->userId)
            ->first();

        if ($record === null) {
            throw NotFound::entity('ai_response', $command->id);
        }

        $currentStatus = AiResponseStatus::from($record->status);
        $newStatus = $currentStatus->transitionTo(AiResponseStatus::from(AiResponseStatus::APPROVED));

        $record->status = $newStatus->value;
        $record->save();

        return null;
    }
}
