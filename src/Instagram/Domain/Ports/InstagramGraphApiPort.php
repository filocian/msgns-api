<?php

declare(strict_types=1);

namespace Src\Instagram\Domain\Ports;

interface InstagramGraphApiPort
{
    /**
     * Exchange a short-lived token for a long-lived token (~60 days).
     * Uses HTTP GET (not POST) — Meta API quirk.
     *
     * @return array{access_token: string, expires_in: int}
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): array;

    /**
     * Fetch the Instagram Business Account ID from a Facebook Page.
     *
     * @return array{id: string}
     */
    public function getInstagramBusinessAccountId(string $pageId, string $accessToken): array;

    /**
     * Create a media container for the given image URL and caption.
     * Step 1 of the 2-step publish flow.
     *
     * @return array{id: string}
     */
    public function createMediaContainer(string $igUserId, string $imageUrl, string $caption, string $accessToken): array;

    /**
     * Poll the container status endpoint every 2 seconds, up to 15 attempts,
     * returning once the status_code is FINISHED.
     *
     * @throws \Src\Instagram\Domain\Errors\InstagramApiUnavailable on ERROR status, timeout, HTTP failure, or connection failure
     */
    public function waitForContainerReady(string $igUserId, string $creationId, string $accessToken): void;

    /**
     * Publish a previously created media container.
     * Step 2 of the 2-step publish flow.
     *
     * @return array{id: string}
     */
    public function publishMediaContainer(string $igUserId, string $creationId, string $accessToken): array;
}
