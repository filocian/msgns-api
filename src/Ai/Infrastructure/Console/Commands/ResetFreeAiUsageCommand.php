<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Console\Commands;

use Illuminate\Console\Command;

final class ResetFreeAiUsageCommand extends Command
{
    protected $signature = 'ai:reset-free-usage';

    protected $description = 'Reset monthly free-tier AI usage records (runs on day 1 of each month)';

    public function handle(): int
    {
        // TODO: Pending BE-5 — AiUsageRecord model not yet available.
        // Implementation: AiUsageRecord::where('source', 'free')->delete();
        $this->info('Free-tier AI usage records have been reset.');

        return self::SUCCESS;
    }
}
