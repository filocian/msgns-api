<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Ports;

interface EmailChangeTokenPort
{
    /**
     * Generate an encrypted token encoding userId + newEmail.
     */
    public function generate(int $userId, string $newEmail): string;

    /**
     * Validate the token and return the decoded payload.
     * Throws ValidationFailed if invalid or expired.
     *
     * @return array{userId: int, newEmail: string}
     */
    public function validate(string $token): array;
}
