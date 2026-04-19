<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Auth;

use Src\Identity\Domain\DTOs\GoogleProfile;
use Src\Identity\Domain\Ports\GoogleAuthPort;
use Src\Shared\Core\Errors\ValidationFailed;

final class GoogleOAuthAdapter implements GoogleAuthPort
{
    public function getProfile(string $idToken): GoogleProfile
    {
        $segments = explode('.', $idToken);

        if (count($segments) !== 3) {
            throw ValidationFailed::because('invalid_google_token');
        }

        $payloadJson = base64_decode(
            str_pad(
                strtr($segments[1], '-_', '+/'),
                strlen($segments[1]) % 4 === 0 ? strlen($segments[1]) : strlen($segments[1]) + (4 - strlen($segments[1]) % 4),
                '=',
                STR_PAD_RIGHT
            )
        );

        $payload = json_decode($payloadJson ?: '{}', true);

        if (!is_array($payload)) {
            throw ValidationFailed::because('invalid_google_token');
        }

        $clientId = config('services.google.sign_in_client_id');
        if (!empty($clientId) && ($payload['aud'] ?? null) !== $clientId) {
            throw ValidationFailed::because('invalid_google_token');
        }

        if (empty($payload['sub']) || empty($payload['email'])) {
            throw ValidationFailed::because('invalid_google_token');
        }

        return new GoogleProfile(
            email: strtolower(trim($payload['email'])),
            name: $payload['name'] ?? $payload['email'],
            googleId: $payload['sub'],
        );
    }
}
