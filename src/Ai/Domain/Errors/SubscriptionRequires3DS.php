<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class SubscriptionRequires3DS extends DomainException
{
    public static function forPaymentIntent(string $clientSecret): self
    {
        return new self('subscription_requires_3ds', Response::HTTP_PAYMENT_REQUIRED, [
            'client_secret' => $clientSecret,
        ]);
    }
}
