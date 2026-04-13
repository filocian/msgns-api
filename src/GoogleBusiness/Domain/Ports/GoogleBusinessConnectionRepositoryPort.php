<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Domain\Ports;

use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;

interface GoogleBusinessConnectionRepositoryPort
{
    /**
     * Find connection by user ID, or null if not connected.
     */
    public function findByUserId(int $userId): ?UserGoogleBusinessConnection;

    /**
     * Create or update the connection for a user (upsert on user_id).
     */
    public function upsertForUser(int $userId, array $attributes): UserGoogleBusinessConnection;

    /**
     * Delete the connection for a user. No-op if not connected.
     */
    public function deleteByUserId(int $userId): void;
}
