<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class SubscriptionNotFound extends DomainException
{
    public static function forUser(int $userId): self
    {
        return new self('subscription_not_found', Response::HTTP_NOT_FOUND, ['user_id' => $userId]);
    }
}
