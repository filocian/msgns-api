<?php

declare(strict_types=1);

use Src\Subscriptions\Domain\Entities\SubscriptionType;
use Src\Subscriptions\Domain\ValueObjects\BillingPeriod;
use Src\Subscriptions\Domain\ValueObjects\SubscriptionMode;

describe('SubscriptionType::create() with Stripe binding', function () {
    it('creates a subscription type with bound stripe product id and price ids', function () {
        $st = SubscriptionType::create(
            name: 'Pro',
            slug: 'pro',
            description: null,
            mode: SubscriptionMode::Classic,
            billingPeriods: [BillingPeriod::Monthly, BillingPeriod::Annual],
            basePriceCents: 999,
            permissionName: 'ai.pro',
            googleReviewLimit: 10,
            instagramContentLimit: 5,
            stripeProductId: 'prod_abc',
            stripePriceIds: [
                'monthly' => 'price_monthly',
                'annual'  => 'price_annual',
            ],
        );

        expect($st->id)->toBe(0)
            ->and($st->name)->toBe('Pro')
            ->and($st->slug)->toBe('pro')
            ->and($st->description)->toBeNull()
            ->and($st->mode)->toBe(SubscriptionMode::Classic)
            ->and($st->billingPeriods)->toBe([BillingPeriod::Monthly, BillingPeriod::Annual])
            ->and($st->basePriceCents)->toBe(999)
            ->and($st->permissionName)->toBe('ai.pro')
            ->and($st->googleReviewLimit)->toBe(10)
            ->and($st->instagramContentLimit)->toBe(5)
            ->and($st->stripeProductId)->toBe('prod_abc')
            ->and($st->stripePriceIds)->toBe([
                'monthly' => 'price_monthly',
                'annual'  => 'price_annual',
            ])
            ->and($st->isActive)->toBeTrue();
    });

    it('creates a subscription type with a single monthly-only price set', function () {
        $st = SubscriptionType::create(
            name: 'Starter',
            slug: 'starter',
            description: null,
            mode: SubscriptionMode::Classic,
            billingPeriods: [BillingPeriod::Monthly],
            basePriceCents: 499,
            permissionName: 'ai.starter',
            googleReviewLimit: 3,
            instagramContentLimit: 1,
            stripeProductId: 'prod_starter',
            stripePriceIds: ['monthly' => 'price_starter_m'],
        );

        expect($st->billingPeriods)->toBe([BillingPeriod::Monthly]);
        expect($st->stripePriceIds)->toBe(['monthly' => 'price_starter_m']);
    });

    it('requires stripeProductId and stripePriceIds as non-nullable parameters', function () {
        $ref    = new ReflectionMethod(SubscriptionType::class, 'create');
        $params = [];
        foreach ($ref->getParameters() as $p) {
            $params[$p->getName()] = $p;
        }

        expect($params)->toHaveKey('stripeProductId');
        expect($params)->toHaveKey('stripePriceIds');

        $productIdType = $params['stripeProductId']->getType();
        expect($productIdType)->toBeInstanceOf(ReflectionNamedType::class);
        expect($productIdType->getName())->toBe('string');
        expect($productIdType->allowsNull())->toBeFalse();

        $priceIdsType = $params['stripePriceIds']->getType();
        expect($priceIdsType)->toBeInstanceOf(ReflectionNamedType::class);
        expect($priceIdsType->getName())->toBe('array');
        expect($priceIdsType->allowsNull())->toBeFalse();
    });
});

describe('SubscriptionType::applyUpdate() reduced signature', function () {
    it('does not expose mode, billingPeriods, or basePriceCents as parameters', function () {
        $ref       = new ReflectionMethod(SubscriptionType::class, 'applyUpdate');
        $paramNames = array_map(
            static fn (ReflectionParameter $p): string => $p->getName(),
            $ref->getParameters(),
        );

        expect($paramNames)->not->toContain('mode');
        expect($paramNames)->not->toContain('billingPeriods');
        expect($paramNames)->not->toContain('basePriceCents');
        expect($paramNames)->not->toContain('stripeProductId');
        expect($paramNames)->not->toContain('stripePriceIds');
    });

    it('mutates name, slug, description, permissionName, googleReviewLimit, instagramContentLimit only', function () {
        $st = SubscriptionType::create(
            name: 'Pro',
            slug: 'pro',
            description: 'Original',
            mode: SubscriptionMode::Classic,
            billingPeriods: [BillingPeriod::Monthly],
            basePriceCents: 999,
            permissionName: 'ai.pro',
            googleReviewLimit: 10,
            instagramContentLimit: 5,
            stripeProductId: 'prod_abc',
            stripePriceIds: ['monthly' => 'price_monthly'],
        );

        $st->applyUpdate(
            name: 'Pro Updated',
            slug: 'pro-updated',
            description: 'Updated description',
            permissionName: 'ai.pro-v2',
            googleReviewLimit: 20,
            instagramContentLimit: 10,
        );

        expect($st->name)->toBe('Pro Updated');
        expect($st->slug)->toBe('pro-updated');
        expect($st->description)->toBe('Updated description');
        expect($st->permissionName)->toBe('ai.pro-v2');
        expect($st->googleReviewLimit)->toBe(20);
        expect($st->instagramContentLimit)->toBe(10);
        // Immutable post-create
        expect($st->mode)->toBe(SubscriptionMode::Classic);
        expect($st->billingPeriods)->toBe([BillingPeriod::Monthly]);
        expect($st->basePriceCents)->toBe(999);
        expect($st->stripeProductId)->toBe('prod_abc');
        expect($st->stripePriceIds)->toBe(['monthly' => 'price_monthly']);
    });

    it('accepts null for optional update fields leaving current values intact', function () {
        $st = SubscriptionType::create(
            name: 'Pro',
            slug: 'pro',
            description: 'Original',
            mode: SubscriptionMode::Classic,
            billingPeriods: [BillingPeriod::Monthly],
            basePriceCents: 999,
            permissionName: 'ai.pro',
            googleReviewLimit: 10,
            instagramContentLimit: 5,
            stripeProductId: 'prod_abc',
            stripePriceIds: ['monthly' => 'price_monthly'],
        );

        $st->applyUpdate(
            name: null,
            slug: null,
            description: null,
            permissionName: null,
            googleReviewLimit: null,
            instagramContentLimit: null,
        );

        expect($st->name)->toBe('Pro');
        expect($st->slug)->toBe('pro');
        expect($st->description)->toBe('Original');
        expect($st->permissionName)->toBe('ai.pro');
        expect($st->googleReviewLimit)->toBe(10);
        expect($st->instagramContentLimit)->toBe(5);
    });
});

describe('SubscriptionType toggleActive', function () {
    it('flips isActive from true to false and back', function () {
        $st = SubscriptionType::create(
            name: 'Pro',
            slug: 'pro',
            description: null,
            mode: SubscriptionMode::Classic,
            billingPeriods: [BillingPeriod::Monthly],
            basePriceCents: 999,
            permissionName: 'ai.pro',
            googleReviewLimit: 10,
            instagramContentLimit: 5,
            stripeProductId: 'prod_abc',
            stripePriceIds: ['monthly' => 'price_monthly'],
        );

        expect($st->isActive)->toBeTrue();
        $st->toggleActive();
        expect($st->isActive)->toBeFalse();
        $st->toggleActive();
        expect($st->isActive)->toBeTrue();
    });
});
