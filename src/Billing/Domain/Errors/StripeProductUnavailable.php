<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class StripeProductUnavailable extends DomainException
{
    public static function withProductId(string $productId): self
    {
        return new self(
            'billing.stripe_product.unavailable',
            Response::HTTP_BAD_GATEWAY,
            ['productId' => $productId],
        );
    }
}
