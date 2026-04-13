<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Adapters;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Src\Instagram\Domain\Errors\InstagramApiUnavailable;
use Src\Instagram\Domain\Ports\InstagramGraphApiPort;

final class InstagramGraphApiAdapter implements InstagramGraphApiPort
{
    private const string API_VERSION = 'v21.0';
    private const string BASE_URL    = 'https://graph.facebook.com';
    private const int    TIMEOUT     = 10;

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
                ->get(self::BASE_URL . '/' . self::API_VERSION . '/' . $pageId, [
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
                ->post(self::BASE_URL . '/' . self::API_VERSION . '/' . $igUserId . '/media', [
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
     * Publish a previously created media container.
     *
     * @return array{id: string}
     */
    public function publishMediaContainer(string $igUserId, string $creationId, string $accessToken): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withToken($accessToken)
                ->post(self::BASE_URL . '/' . self::API_VERSION . '/' . $igUserId . '/media_publish', [
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
}
