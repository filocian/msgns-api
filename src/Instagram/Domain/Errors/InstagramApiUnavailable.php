<?php

declare(strict_types=1);

namespace Src\Instagram\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class InstagramApiUnavailable extends DomainException
{
    /**
     * @param array<string, mixed> $context
     */
    public static function because(string $reason, array $context = []): self
    {
        return new self(
            'instagram_api_unavailable',
            Response::HTTP_BAD_GATEWAY,
            ['reason' => $reason] + $context,
        );
    }
}
