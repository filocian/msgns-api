<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Infrastructure\Services;

use Illuminate\Support\Facades\Http;
use Src\GoogleBusiness\Domain\Errors\GoogleBusinessOAuthFailed;

final class GoogleBusinessOAuthService
{
    private const string AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const string TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    private const string USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';
    private const string SCOPE        = 'https://www.googleapis.com/auth/business.manage';

    public function buildAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id'     => config('services.google.client_id'),
            'redirect_uri'  => config('services.google.business_redirect_uri'),
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);

        return self::AUTH_URL . '?' . $params;
    }

    /**
     * Exchange an authorization code for access and refresh tokens.
     *
     * @return array{access_token: string, refresh_token: string|null, expires_in: int}
     *
     * @throws GoogleBusinessOAuthFailed
     */
    public function exchangeCodeForTokens(string $code): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post(self::TOKEN_URL, [
                    'code'          => $code,
                    'client_id'     => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'redirect_uri'  => config('services.google.business_redirect_uri'),
                    'grant_type'    => 'authorization_code',
                ]);
        } catch (\Throwable) {
            throw GoogleBusinessOAuthFailed::because('code_exchange_connection_failed');
        }

        if (! $response->successful()) {
            throw GoogleBusinessOAuthFailed::because('code_exchange_failed');
        }

        return [
            'access_token'  => (string) $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token'),
            'expires_in'    => (int) $response->json('expires_in', 3600),
        ];
    }

    /**
     * Fetch the Google account ID (sub) for the authenticated user.
     *
     * @throws GoogleBusinessOAuthFailed
     */
    public function fetchGoogleAccountId(string $accessToken): string
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get(self::USERINFO_URL);
        } catch (\Throwable) {
            throw GoogleBusinessOAuthFailed::because('userinfo_connection_failed');
        }

        if (! $response->successful()) {
            throw GoogleBusinessOAuthFailed::because('userinfo_fetch_failed');
        }

        $sub = $response->json('sub');

        if (empty($sub)) {
            throw GoogleBusinessOAuthFailed::because('userinfo_missing_sub');
        }

        return (string) $sub;
    }
}
