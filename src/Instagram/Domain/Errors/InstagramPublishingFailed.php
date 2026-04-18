<?php

declare(strict_types=1);

namespace Src\Instagram\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class InstagramPublishingFailed extends DomainException
{
    /**
     * @param array<string, mixed> $context
     */
    public static function because(string $reason, array $context = []): self
    {
        return new self(
            'instagram_publishing_failed',
            Response::HTTP_BAD_GATEWAY,
            ['reason' => $reason] + $context,
        );
    }
}
