<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Infrastructure\Adapters;

use Illuminate\Support\Facades\Http;
use Src\GoogleBusiness\Domain\Errors\GoogleBusinessUnavailable;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessApiPort;

final class GoogleBusinessApiAdapter implements GoogleBusinessApiPort
{
    private const string REVIEWS_BASE_URL = 'https://mybusiness.googleapis.com/v4';
    private const string TOKEN_URL        = 'https://oauth2.googleapis.com/token';

    public function fetchPendingReviews(string $accessToken, string $accountId, string $locationId): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get(self::REVIEWS_BASE_URL . "/accounts/{$accountId}/locations/{$locationId}/reviews", [
                    'filter' => 'has_reply=false',
                ]);
        } catch (\Throwable $e) {
            throw GoogleBusinessUnavailable::because('google_api_connection_failed');
        }

        if ($response->status() === 429) {
            throw GoogleBusinessUnavailable::because('google_api_rate_limited');
        }

        if (! $response->successful()) {
            throw GoogleBusinessUnavailable::because('google_api_error');
        }

        /** @var array<int, array<string, mixed>> $reviews */
        $reviews = $response->json()['reviews'] ?? [];

        return $reviews;
    }

    public function postReviewReply(string $accessToken, string $accountId, string $locationId, string $reviewId, string $replyText): void
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->put(self::REVIEWS_BASE_URL . "/accounts/{$accountId}/locations/{$locationId}/reviews/{$reviewId}/reply", [
                    'comment' => $replyText,
                ]);
        } catch (\Throwable $e) {
            throw GoogleBusinessUnavailable::because('google_api_connection_failed');
        }

        if ($response->status() === 429) {
            throw GoogleBusinessUnavailable::because('google_api_rate_limited');
        }

        if (! $response->successful()) {
            throw GoogleBusinessUnavailable::because('google_api_error');
        }
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post(self::TOKEN_URL, [
                    'client_id'     => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'refresh_token' => $refreshToken,
                    'grant_type'    => 'refresh_token',
                ]);
        } catch (\Throwable $e) {
            throw GoogleBusinessUnavailable::because('token_refresh_connection_failed');
        }

        if (! $response->successful()) {
            throw GoogleBusinessUnavailable::because('token_refresh_failed');
        }

        return [
            'access_token' => (string) $response->json('access_token'),
            'expires_in'   => (int) $response->json('expires_in'),
        ];
    }
}
