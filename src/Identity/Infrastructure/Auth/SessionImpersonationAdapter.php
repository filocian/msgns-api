<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Src\Identity\Domain\Ports\ImpersonationPort;
use Src\Shared\Core\Errors\NotFound;

final class SessionImpersonationAdapter implements ImpersonationPort
{
    public function startImpersonation(int $adminUserId, int $targetUserId): void
    {
        $target = User::find($targetUserId);
        if (!$target) {
            throw NotFound::because('user_not_found');
        }
        session()->put('impersonator_id', $adminUserId);
        Auth::login($target);
    }

    public function stopImpersonation(): int
    {
        $adminId = (int) session()->pull('impersonator_id');
        $admin = User::find($adminId);
        if (!$admin) {
            throw NotFound::because('user_not_found');
        }
        Auth::login($admin);
        return $adminId;
    }

    public function isImpersonating(): bool
    {
        return session()->has('impersonator_id');
    }

    public function getImpersonatorId(): ?int
    {
        $id = session()->get('impersonator_id');
        return $id !== null ? (int) $id : null;
    }
}
