<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Src\Identity\Domain\Events\PasswordReset;

final class TrackPasswordReset
{
    public function handle(PasswordReset $event): void
    {
        Log::info('Password reset completed', ['user_id' => $event->userId]);
    }
}
