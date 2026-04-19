<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Adapters;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Src\Instagram\Domain\Errors\InstagramApiUnavailable;
use Src\Instagram\Domain\Ports\InstagramGraphApiPort;

class InstagramGraphApiAdapter implements InstagramGraphApiPort
{
    private const string BASE_URL              = 'https://graph.facebook.com';
    private const int    TIMEOUT               = 10;
    private const int    POLL_INTERVAL_SECONDS = 2;
    private const int    POLL_MAX_ATTEMPTS     = 15;

    /**
     * Resolve the Graph API version from config (defaults to v22.0).
     */
    private function apiVersion(): string
    {
        return (string) config('services.meta.graph_api_version', 'v22.0');
    }

    /**
     * Exchange a short-lived token for a long-lived token (~60 days).
     * Uses GET, not POST — Meta API quirk.
     *
     * @return array{access_token: string, expires_in: int}
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)->get(self::BASE_URL . '/oauth/access_token', [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => config('services.meta.app_id'),
                'client_secret'     => config('services.meta.app_secret'),
                'fb_exchange_token' => $shortLivedToken,
            ]);
        } catch (ConnectionException) {
            throw InstagramApiUnavailable::because('connection_failed');
        }

        if ($response->failed()) {
            throw InstagramApiUnavailable::because('long_lived_token_exchange_failed');
        }

        return [
            'access_token' => (string) $response->json('access_token'),
            'expires_in'   => (int) $response->json('expires_in'),
        ];
    }

    /**
     * Fetch the Instagram Business Account ID from a Facebook Page.
     *
     * @return array{id: string}
     */
    public function getInstagramBusinessAccountId(string $pageId, string $accessToken): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withToken($accessToken)
                ->get(self::BASE_URL . '/' . $this->apiVersion() . '/' . $pageId, [
                    'fields' => 'instagram_business_account',
                ]);
        } catch (ConnectionException) {
            throw InstagramApiUnavailable::because('connection_failed');
        }

        if ($response->failed()) {
            throw InstagramApiUnavailable::because('instagram_business_account_fetch_failed');
        }

        $igAccount = $response->json('instagram_business_account');

        if (empty($igAccount['id'])) {
            throw InstagramApiUnavailable::because('instagram_business_account_fetch_failed');
        }

        return ['id' => (string) $igAccount['id']];
    }

    /**
     * Create a media container for the given image URL and caption.
     *
     * @return array{id: string}
     */
    public function createMediaContainer(string $igUserId, string $imageUrl, string $caption, string $accessToken): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withToken($accessToken)
                ->post(self::BASE_URL . '/' . $this->apiVersion() . '/' . $igUserId . '/media', [
                    'image_url' => $imageUrl,
                    'caption'   => $caption,
                ]);
        } catch (ConnectionException) {
            throw InstagramApiUnavailable::because('connection_failed');
        }

        if ($response->failed()) {
            throw InstagramApiUnavailable::because('media_container_creation_failed');
        }

        return ['id' => (string) $response->json('id')];
    }

    /**
     * Poll `/{version}/{creationId}?fields=status_code` every 2 seconds, up to 15 attempts.
     * Returns as soon as status_code is FINISHED.
     *
     * @throws InstagramApiUnavailable on ERROR status, HTTP failure, ConnectionException, or timeout after max attempts
     */
    public function waitForContainerReady(string $igUserId, string $creationId, string $accessToken): void
    {
        $url = self::BASE_URL . '/' . $this->apiVersion() . '/' . $creationId;

        for ($attempt = 0; $attempt < self::POLL_MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::timeout(self::TIMEOUT)
                    ->withToken($accessToken)
                    ->get($url, ['fields' => 'status_code']);
            } catch (ConnectionException) {
                throw InstagramApiUnavailable::because('connection_failed');
            }

            if ($response->failed()) {
                throw InstagramApiUnavailable::because('container_status_error', [
                    'status_code' => 'http_' . $response->status(),
                ]);
            }

            $status = (string) $response->json('status_code');

            if ($status === 'FINISHED' || $status === 'PUBLISHED') {
                return;
            }

            if ($status === 'ERROR' || $status === 'EXPIRED') {
                throw InstagramApiUnavailable::because('container_status_error', [
                    'status_code' => $status,
                ]);
            }

            // Any other status (IN_PROGRESS, unknown) → keep polling.
            // Only sleep between polls, NOT after the final attempt.
            if ($attempt < self::POLL_MAX_ATTEMPTS - 1) {
                $this->sleep(self::POLL_INTERVAL_SECONDS);
            }
        }

        throw InstagramApiUnavailable::because('container_timeout');
    }

    /**
     * Publish a previously created media container.
     *
     * @return array{id: string}
     */
    public function publishMediaContainer(string $igUserId, string $creationId, string $accessToken): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withToken($accessToken)
                ->post(self::BASE_URL . '/' . $this->apiVersion() . '/' . $igUserId . '/media_publish', [
                    'creation_id' => $creationId,
                ]);
        } catch (ConnectionException) {
            throw InstagramApiUnavailable::because('connection_failed');
        }

        if ($response->failed()) {
            throw InstagramApiUnavailable::because('media_publish_failed');
        }

        return ['id' => (string) $response->json('id')];
    }

    /**
     * Sleep helper — extracted so tests can override without real delays.
     */
    protected function sleep(int $seconds): void
    {
        sleep($seconds);
    }
}
