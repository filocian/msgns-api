<?php

declare(strict_types=1);

use Src\Subscriptions\Domain\Errors\SubscriptionTypeStripeProductDuplicate;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeStripeProductInvalidCurrency;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeStripeProductMixedPrices;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeStripeProductNoMonthlyPrice;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeStripeProductNotFound;
use Symfony\Component\HttpFoundation\Response;

describe('Subscriptions Stripe-binding domain errors', function () {
    it('SubscriptionTypeStripeProductNotFound carries the expected code, status, and context', function () {
        $e = SubscriptionTypeStripeProductNotFound::withProductId('prod_abc');

        expect($e->errorCode())->toBe('subscription_types.stripe_product.not_found');
        expect($e->httpStatus())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        expect($e->context())->toBe(['stripe_product_id' => 'prod_abc']);
    });

    it('SubscriptionTypeStripeProductMixedPrices carries the expected code, status, and context', function () {
        $e = SubscriptionTypeStripeProductMixedPrices::withProductId('prod_abc');

        expect($e->errorCode())->toBe('subscription_types.stripe_product.mixed_prices');
        expect($e->httpStatus())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        expect($e->context())->toBe(['stripe_product_id' => 'prod_abc']);
    });

    it('SubscriptionTypeStripeProductNoMonthlyPrice carries the expected code, status, and context', function () {
        $e = SubscriptionTypeStripeProductNoMonthlyPrice::withProductId('prod_abc');

        expect($e->errorCode())->toBe('subscription_types.stripe_product.no_monthly_price');
        expect($e->httpStatus())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        expect($e->context())->toBe(['stripe_product_id' => 'prod_abc']);
    });

    it('SubscriptionTypeStripeProductInvalidCurrency carries the expected code, status, and context', function () {
        $e = SubscriptionTypeStripeProductInvalidCurrency::withProductId('prod_abc', 'usd');

        expect($e->errorCode())->toBe('subscription_types.stripe_product.invalid_currency');
        expect($e->httpStatus())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        expect($e->context())->toBe([
            'stripe_product_id' => 'prod_abc',
            'currency'          => 'usd',
        ]);
    });

    it('SubscriptionTypeStripeProductDuplicate carries the expected code, status, and context', function () {
        $e = SubscriptionTypeStripeProductDuplicate::withProductId('prod_abc');

        expect($e->errorCode())->toBe('subscription_types.stripe_product_id.duplicate');
        expect($e->httpStatus())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        expect($e->context())->toBe(['stripe_product_id' => 'prod_abc']);
    });
});
