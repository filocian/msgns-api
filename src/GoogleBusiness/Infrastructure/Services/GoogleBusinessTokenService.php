<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Infrastructure\Services;

use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessApiPort;

final class GoogleBusinessTokenService
{
    public function __construct(
        private readonly GoogleBusinessApiPort $api,
    ) {}

    /**
     * Ensures the connection has a fresh access token.
     * Triggers a refresh if the token is expired or within the 5-minute buffer.
     * Never overwrites refresh_token — only access_token and token_expires_at are updated.
     */
    public function ensureFreshToken(UserGoogleBusinessConnection $connection): UserGoogleBusinessConnection
    {
        if (! $connection->isTokenExpired()) {
            return $connection;
        }

        $data = $this->api->refreshAccessToken((string) $connection->refresh_token);

        $connection->access_token    = $data['access_token'];
        $connection->token_expires_at = now()->addSeconds($data['expires_in']);
        $connection->save();

        return $connection;
    }
}
