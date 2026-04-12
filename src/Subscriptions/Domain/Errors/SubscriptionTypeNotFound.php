<?php

declare(strict_types=1);

namespace Src\Subscriptions\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class SubscriptionTypeNotFound extends DomainException
{
    public static function withId(int $id): self
    {
        return new self(
            'subscription_type_not_found',
            Response::HTTP_NOT_FOUND,
            ['id' => $id],
        );
    }
}
