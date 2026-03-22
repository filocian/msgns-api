<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Ports;

interface ImpersonationPort
{
    public function startImpersonation(int $adminUserId, int $targetUserId): void;
    public function stopImpersonation(): int; // returns admin user id
    public function isImpersonating(): bool;
    public function getImpersonatorId(): ?int;
}
