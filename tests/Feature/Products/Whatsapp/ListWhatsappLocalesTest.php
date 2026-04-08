<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;

beforeEach(function () {
    $this->seed(ProductConfigurationStatusSeeder::class);

    $this->user = $this->create_user(['email' => 'whatsapp-locales@example.com']);
    $this->actingAs($this->user, 'stateful-api');
});

describe('GET /api/v2/products/whatsapp/locales', function () {
    it('returns a list of locales with only the code field', function () {
        $response = $this->getJson('/api/v2/products/whatsapp/locales');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'locales' => [
                        '*' => ['code'],
                    ],
                ],
            ]);

        $locales = $response->json('data.locales');

        expect($locales)->toBeArray()
            ->and(count($locales))->toBeGreaterThan(0);

        // Verify no 'id' field is exposed
        foreach ($locales as $locale) {
            expect($locale)->toHaveKey('code')
                ->and($locale)->not->toHaveKey('id');
        }

        // Verify known locale codes are present (seeded via migration)
        $codes = array_column($locales, 'code');
        expect($codes)->toContain('es_ES')
            ->and($codes)->toContain('en_US')
            ->and($codes)->toContain('de_DE');
    });

    it('returns 401 when unauthenticated', function () {
        auth()->guard('stateful-api')->logout();

        $this->getJson('/api/v2/products/whatsapp/locales')
            ->assertStatus(401);
    });
});
