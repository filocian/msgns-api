<?php

declare(strict_types=1);

use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

// ─── GET /api/v2/ai/subscription-types ───────────────────────────────────────

describe('GET /api/v2/ai/subscription-types', function (): void {

    it('returns only active subscription types without authentication', function (): void {
        SubscriptionTypeModel::factory()->count(2)->create(['is_active' => true]);
        SubscriptionTypeModel::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v2/ai/subscription-types')
            ->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThanOrEqual(2);
        expect(collect($data)->pluck('id'))->not->toContain(
            SubscriptionTypeModel::query()->where('is_active', false)->first()?->id
        );
    });

    it('returns correct shape for each subscription type', function (): void {
        SubscriptionTypeModel::factory()->create([
            'is_active'               => true,
            'name'                    => 'Basic Plan',
            'mode'                    => 'classic',
            'billing_periods'         => ['monthly', 'annual'],
            'google_review_limit'     => 50,
            'instagram_content_limit' => 10,
        ]);

        $this->getJson('/api/v2/ai/subscription-types')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'mode',
                        'base_price_cents',
                        'permission_name',
                        'google_review_limit',
                        'instagram_content_limit',
                    ],
                ],
            ]);
    });

    it('returns empty array when no active subscription types exist', function (): void {
        SubscriptionTypeModel::query()->update(['is_active' => false]);

        $this->getJson('/api/v2/ai/subscription-types')
            ->assertStatus(200)
            ->assertJson(['data' => []]);
    });

    it('excludes inactive subscription types from the response', function (): void {
        $active   = SubscriptionTypeModel::factory()->create(['is_active' => true]);
        $inactive = SubscriptionTypeModel::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v2/ai/subscription-types')
            ->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        expect($ids)->toContain($active->id)
            ->and($ids)->not->toContain($inactive->id);
    });

});
