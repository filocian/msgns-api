<?php

declare(strict_types=1);

namespace Src\Subscriptions\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class SubscriptionTypeHasActiveSubscriptions extends DomainException
{
    public static function forType(int $subscriptionTypeId): self
    {
        return new self(
            'subscription_type_has_active_subscriptions',
            Response::HTTP_CONFLICT,
            ['subscription_type_id' => $subscriptionTypeId],
        );
    }
}
