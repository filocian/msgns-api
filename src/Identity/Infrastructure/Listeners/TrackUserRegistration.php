<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Listeners;

use Src\Identity\Domain\Events\UserRegistered;
use Illuminate\Support\Facades\Log;

final class TrackUserRegistration
{
    public function handle(UserRegistered $event): void
    {
        Log::info('User registered', ['user_id' => $event->userId, 'email' => $event->email]);
    }
}
