<?php

declare(strict_types=1);

use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

it('returns 200 with only active subscription types without auth', function () {
    SubscriptionTypeModel::factory()->count(2)->create(['is_active' => true]);

    $response = $this->getJson('/api/v2/subscriptions/subscription-types')
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(2);
});

it('excludes inactive subscription types', function () {
    SubscriptionTypeModel::factory()->create(['is_active' => true]);
    SubscriptionTypeModel::factory()->create(['is_active' => false]);

    $response = $this->getJson('/api/v2/subscriptions/subscription-types')
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(1);
});

it('excludes soft-deleted subscription types', function () {
    $active  = SubscriptionTypeModel::factory()->create(['is_active' => true]);
    $deleted = SubscriptionTypeModel::factory()->create(['is_active' => true]);
    $deleted->delete();

    $response = $this->getJson('/api/v2/subscriptions/subscription-types')
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($active->id);
});

it('response does not contain permissionName, stripeProductId, stripePriceIds fields', function () {
    SubscriptionTypeModel::factory()->create(['is_active' => true]);

    $response = $this->getJson('/api/v2/subscriptions/subscription-types')
        ->assertStatus(200);

    $item = $response->json('data.0');

    expect($item)->not->toHaveKey('permissionName')
        ->and($item)->not->toHaveKey('stripeProductId')
        ->and($item)->not->toHaveKey('stripePriceIds')
        ->and($item)->not->toHaveKey('isActive')
        ->and($item)->not->toHaveKey('createdAt')
        ->and($item)->not->toHaveKey('updatedAt');
});
