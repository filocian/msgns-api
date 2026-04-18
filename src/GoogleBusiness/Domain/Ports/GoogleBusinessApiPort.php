<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Domain\Ports;

use Src\GoogleBusiness\Domain\Errors\GoogleBusinessUnavailable;

interface GoogleBusinessApiPort
{
    /**
     * Fetch reviews that have no owner reply yet.
     *
     * @return array<int, array<string, mixed>> Raw Google API response payload (reviews array)
     *
     * @throws GoogleBusinessUnavailable On HTTP failure, timeout, or 429
     */
    public function fetchPendingReviews(string $accessToken, string $accountId, string $locationId): array;

    /**
     * Post a reply to a specific review.
     *
     * @throws GoogleBusinessUnavailable On HTTP failure, timeout, or 429
     */
    public function postReviewReply(string $accessToken, string $accountId, string $locationId, string $reviewId, string $replyText): void;

    /**
     * Exchange a refresh token for a new access token.
     *
     * @return array{access_token: string, expires_in: int}
     *
     * @throws GoogleBusinessUnavailable On HTTP failure or invalid refresh token
     */
    public function refreshAccessToken(string $refreshToken): array;
}
