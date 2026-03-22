<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Listeners;

use Src\Identity\Domain\Events\UserLoggedIn;
use Illuminate\Support\Facades\Log;

final class TrackUserLogin
{
    public function handle(UserLoggedIn $event): void
    {
        Log::info('User logged in', ['user_id' => $event->userId, 'method' => $event->method]);
    }
}
