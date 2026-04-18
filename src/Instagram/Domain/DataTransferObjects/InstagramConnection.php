<?php

declare(strict_types=1);

namespace Src\Instagram\Domain\DataTransferObjects;

/**
 * Immutable value object representing a user's Instagram OAuth connection.
 *
 * Returned by {@see \Src\Instagram\Domain\Ports\InstagramConnectionRepositoryPort}.
 * Keeps the Application/Domain layers free from Illuminate\* imports.
 *
 * When {@see self::$expiresAt} is null, the token is treated as non-expiring and
 * {@see self::isExpired()} returns false.
 */
final readonly class InstagramConnection
{
    public function __construct(
        public int $userId,
        public string $accessToken,
        public string $instagramUserId,
        public string $instagramUsername,
        public string $pageId,
        public ?\DateTimeImmutable $expiresAt,
    ) {}

    /**
     * Returns true when the connection's access token has expired relative to $now.
     *
     * A null $expiresAt is treated as non-expiring (always returns false).
     * When $now is null, the current system time is used.
     */
    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < ($now ?? new \DateTimeImmutable());
    }
}
