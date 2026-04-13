<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Listeners;

use App\Models\User;
use Src\Identity\Domain\Events\UserActivated;
use Src\Identity\Domain\Permissions\DomainPermissions;

final class AssignFreeAiPermissionListener
{
    public function handle(UserActivated $event): void
    {
        $user = User::findOrFail($event->userId);

        if ($user->hasPermissionTo(DomainPermissions::AI_FREE_PREVIEW)) {
            return;
        }

        $user->givePermissionTo(DomainPermissions::AI_FREE_PREVIEW);
    }
}
