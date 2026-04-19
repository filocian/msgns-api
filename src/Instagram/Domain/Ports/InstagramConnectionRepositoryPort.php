<?php

declare(strict_types=1);

namespace Src\Instagram\Domain\Ports;

use Src\Instagram\Domain\DataTransferObjects\InstagramConnection;

interface InstagramConnectionRepositoryPort
{
    /**
     * Fetch the Instagram connection for the given user as a DTO.
     *
     * Returns null when no connection row exists for the user.
     * Expiry is NOT filtered here — callers decide how to treat expired
     * connections via {@see InstagramConnection::isExpired()}.
     */
    public function findByUserId(int $userId): ?InstagramConnection;
}
