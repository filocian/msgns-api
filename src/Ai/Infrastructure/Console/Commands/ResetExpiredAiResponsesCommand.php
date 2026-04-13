<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecord;

final class ResetExpiredAiResponsesCommand extends Command
{
    protected $signature = 'ai:expire-responses';

    protected $description = 'Mark non-terminal AI responses past their expiry date as expired';

    public function handle(): int
    {
        $count = AiResponseRecord::query()
            ->whereIn('status', [
                AiResponseStatus::PENDING,
                AiResponseStatus::APPROVED,
                AiResponseStatus::EDITED,
            ])
            ->where('expires_at', '<', now())
            ->update(['status' => AiResponseStatus::EXPIRED]);

        $this->info("Expired {$count} AI response(s).");

        return self::SUCCESS;
    }
}
