<?php

declare(strict_types=1);

namespace Src\Instagram\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class InstagramOAuthFailed extends DomainException
{
    public static function because(string $reason): self
    {
        return new self('instagram_oauth_failed', Response::HTTP_UNPROCESSABLE_ENTITY, ['reason' => $reason]);
    }
}
