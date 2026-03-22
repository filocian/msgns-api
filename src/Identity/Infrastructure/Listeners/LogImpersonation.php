<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Listeners;

use Src\Identity\Domain\Events\ImpersonationStarted;
use Illuminate\Support\Facades\Log;

final class LogImpersonation
{
    public function handle(ImpersonationStarted $event): void
    {
        Log::info('Impersonation started', [
            'admin_user_id'  => $event->adminUserId,
            'target_user_id' => $event->targetUserId,
        ]);
    }
}
