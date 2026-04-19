<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class SubscriptionAlreadyActive extends DomainException
{
    public static function forUser(int $userId): self
    {
        return new self('subscription_already_active', Response::HTTP_CONFLICT, ['user_id' => $userId]);
    }
}
