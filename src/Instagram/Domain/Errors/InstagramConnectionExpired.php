<?php

declare(strict_types=1);

namespace Src\Instagram\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class InstagramConnectionExpired extends DomainException
{
    public static function forUser(int $userId): self
    {
        return new self('instagram_connection_expired', Response::HTTP_UNAUTHORIZED, ['user_id' => $userId]);
    }
}
