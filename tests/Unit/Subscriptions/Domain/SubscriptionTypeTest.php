<?php

declare(strict_types=1);

use Src\Subscriptions\Domain\Entities\SubscriptionType;
use Src\Subscriptions\Domain\ValueObjects\BillingPeriod;
use Src\Subscriptions\Domain\ValueObjects\SubscriptionMode;

it('creates a subscription type with correct defaults via create()', function () {
    $st = SubscriptionType::create(
        name: 'Test Plan',
        slug: 'test-plan',
        description: null,
        mode: SubscriptionMode::Classic,
        billingPeriods: [BillingPeriod::Monthly],
        basePriceCents: 100,
        permissionName: 'ai.test-plan',
        googleReviewLimit: 10,
        instagramContentLimit: 5,
    );

    expect($st->id)->toBe(0)
        ->and($st->name)->toBe('Test Plan')
        ->and($st->slug)->toBe('test-plan')
        ->and($st->description)->toBeNull()
        ->and($st->mode)->toBe(SubscriptionMode::Classic)
        ->and($st->billingPeriods)->toBe([BillingPeriod::Monthly])
        ->and($st->basePriceCents)->toBe(100)
        ->and($st->permissionName)->toBe('ai.test-plan')
        ->and($st->googleReviewLimit)->toBe(10)
        ->and($st->instagramContentLimit)->toBe(5)
        ->and($st->isActive)->toBeTrue()
        ->and($st->stripeProductId)->toBeNull()
        ->and($st->stripePriceIds)->toBeNull();
});

it('toggleActive flips isActive from true to false', function () {
    $st = SubscriptionType::create(
        name: 'Test Plan',
        slug: 'test-plan',
        description: null,
        mode: SubscriptionMode::Classic,
        billingPeriods: [BillingPeriod::Monthly],
        basePriceCents: 100,
        permissionName: 'ai.test-plan',
        googleReviewLimit: 10,
        instagramContentLimit: 5,
    );

    expect($st->isActive)->toBeTrue();
    $st->toggleActive();
    expect($st->isActive)->toBeFalse();
});

it('toggleActive flips isActive from false to true', function () {
    $st = SubscriptionType::create(
        name: 'Test Plan',
        slug: 'test-plan',
        description: null,
        mode: SubscriptionMode::Classic,
        billingPeriods: [BillingPeriod::Monthly],
        basePriceCents: 100,
        permissionName: 'ai.test-plan',
        googleReviewLimit: 10,
        instagramContentLimit: 5,
    );

    $st->toggleActive();
    expect($st->isActive)->toBeFalse();
    $st->toggleActive();
    expect($st->isActive)->toBeTrue();
});

it('applyUpdate changes name and slug', function () {
    $st = SubscriptionType::create(
        name: 'Test Plan',
        slug: 'test-plan',
        description: null,
        mode: SubscriptionMode::Classic,
        billingPeriods: [BillingPeriod::Monthly],
        basePriceCents: 100,
        permissionName: 'ai.test-plan',
        googleReviewLimit: 10,
        instagramContentLimit: 5,
    );

    $st->applyUpdate(
        name: 'Updated Plan',
        slug: 'updated-plan',
        description: null,
        mode: null,
        billingPeriods: [BillingPeriod::Monthly],
        basePriceCents: null,
        permissionName: null,
        googleReviewLimit: null,
        instagramContentLimit: null,
    );

    expect($st->name)->toBe('Updated Plan')
        ->and($st->slug)->toBe('updated-plan');
});

it('applyUpdate changes mode and billingPeriods', function () {
    $st = SubscriptionType::create(
        name: 'Test Plan',
        slug: 'test-plan',
        description: null,
        mode: SubscriptionMode::Classic,
        billingPeriods: [BillingPeriod::Monthly],
        basePriceCents: 100,
        permissionName: 'ai.test-plan',
        googleReviewLimit: 10,
        instagramContentLimit: 5,
    );

    $st->applyUpdate(
        name: null,
        slug: null,
        description: null,
        mode: SubscriptionMode::Prepaid,
        billingPeriods: null,
        basePriceCents: null,
        permissionName: null,
        googleReviewLimit: null,
        instagramContentLimit: null,
    );

    expect($st->mode)->toBe(SubscriptionMode::Prepaid)
        ->and($st->billingPeriods)->toBeNull();
});

it('applyUpdate always sets billingPeriods — even to null — for prepaid mode', function () {
    $st = SubscriptionType::create(
        name: 'Test Plan',
        slug: 'test-plan',
        description: null,
        mode: SubscriptionMode::Classic,
        billingPeriods: [BillingPeriod::Monthly, BillingPeriod::Annual],
        basePriceCents: 100,
        permissionName: 'ai.test-plan',
        googleReviewLimit: 10,
        instagramContentLimit: 5,
    );

    $st->applyUpdate(
        name: null,
        slug: null,
        description: null,
        mode: SubscriptionMode::Prepaid,
        billingPeriods: null,
        basePriceCents: null,
        permissionName: null,
        googleReviewLimit: null,
        instagramContentLimit: null,
    );

    expect($st->billingPeriods)->toBeNull();
});
