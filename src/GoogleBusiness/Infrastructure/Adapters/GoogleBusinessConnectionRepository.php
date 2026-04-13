<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Infrastructure\Adapters;

use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessConnectionRepositoryPort;

final class GoogleBusinessConnectionRepository implements GoogleBusinessConnectionRepositoryPort
{
    public function findByUserId(int $userId): ?UserGoogleBusinessConnection
    {
        return UserGoogleBusinessConnection::where('user_id', $userId)->first();
    }

    public function upsertForUser(int $userId, array $attributes): UserGoogleBusinessConnection
    {
        $connection = UserGoogleBusinessConnection::updateOrCreate(
            ['user_id' => $userId],
            $attributes,
        );

        return $connection->fresh() ?? $connection;
    }

    public function deleteByUserId(int $userId): void
    {
        UserGoogleBusinessConnection::where('user_id', $userId)->delete();
    }
}
