<?php

declare(strict_types=1);

namespace Src\Subscriptions\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class SubscriptionTypeStripeProductMixedPrices extends DomainException
{
    public static function withProductId(string $productId): self
    {
        return new self(
            'subscription_types.stripe_product.mixed_prices',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            ['stripe_product_id' => $productId],
        );
    }
}
