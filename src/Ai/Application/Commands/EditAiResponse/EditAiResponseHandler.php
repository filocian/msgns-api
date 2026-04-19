<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\EditAiResponse;

use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class EditAiResponseHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof EditAiResponseCommand);

        $record = AiResponseRecordModel::where('id', $command->id)
            ->where('user_id', $command->userId)
            ->first();

        if ($record === null) {
            throw NotFound::entity('ai_response', $command->id);
        }

        $currentStatus = AiResponseStatus::from($record->status);

        // Re-edit idempotency: if already edited, just update content
        if ($currentStatus->equals(AiResponseStatus::from(AiResponseStatus::EDITED))) {
            $record->edited_content = $command->editedContent;
            $record->save();

            return null;
        }

        // Normal transition: pending → edited
        $newStatus = $currentStatus->transitionTo(AiResponseStatus::from(AiResponseStatus::EDITED));
        $record->status = $newStatus->value;
        $record->edited_content = $command->editedContent;
        $record->save();

        return null;
    }
}
