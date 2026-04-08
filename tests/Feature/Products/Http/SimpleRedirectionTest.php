<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\Ports\ProductUsagePort;

require_once __DIR__ . '/../../../Support/ProductRedirectionHelpers.php';

beforeEach(function () {
    $this->seed(ProductConfigurationStatusSeeder::class);

    app()->bind(ProductUsagePort::class, static fn (): ProductUsagePort => new class implements ProductUsagePort
    {
        public function writeUsageEvent(int $productId, int $userId, string $productName, DateTimeImmutable $timestamp): void {}

        public function queryProductUsage(int $productId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
        {
            return [];
        }

        public function deleteProductUsage(int $productId): void {}
    });
});

describe('GET /api/v2/products/{id}/{password}/redirection-target', function () {
    it('returns 200 with target_url and type for a valid simple redirection product', function () {
        $product = createRedirectionProduct();

        $this->getJson("/api/v2/products/{$product['id']}/test-pass/redirection-target")
            ->assertOk()
            ->assertJsonPath('data.url', 'https://google.com')
            ->assertJsonPath('data.type', 'external_url');
    });

    it('returns 200 for each simple model', function (string $model) {
        $product = createRedirectionProduct(['model' => $model]);

        $this->getJson("/api/v2/products/{$product['id']}/test-pass/redirection-target")
            ->assertOk();
    })->with(['instagram', 'youtube', 'tiktok', 'facebook', 'info']);

    it('returns 404 for non-existent product', function () {
        $this->getJson('/api/v2/products/999999/fake-pass/redirection-target')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'product_not_found');
    });

    it('returns 404 for wrong password', function () {
        $product = createRedirectionProduct(['password' => 'correct']);

        $this->getJson("/api/v2/products/{$product['id']}/wrong/redirection-target")
            ->assertNotFound();
    });

    it('returns 404 for soft-deleted product', function () {
        $product = createRedirectionProduct();
        DB::table('products')->where('id', $product['id'])->update(['deleted_at' => now()]);

        $this->getJson("/api/v2/products/{$product['id']}/test-pass/redirection-target")
            ->assertNotFound();
    });

    it('returns 200 with frontend_route for inactive (disabled) product', function () {
        $userId = \App\Models\User::factory()->create()->id;
        $product = createRedirectionProduct([
            'active' => false,
            'user_id' => $userId,
            'configuration_status' => 'completed',
        ]);

        $this->getJson("/api/v2/products/{$product['id']}/test-pass/redirection-target")
            ->assertOk()
            ->assertJsonPath('data.type', 'frontend_route');
    });

    it('returns 200 with frontend_route for product with incomplete configuration', function () {
        $product = createRedirectionProduct([
            'configuration_status' => 'assigned',
            'user_id' => null,
            'target_url' => null,
        ]);

        $this->getJson("/api/v2/products/{$product['id']}/test-pass/redirection-target")
            ->assertOk()
            ->assertJsonPath('data.type', 'frontend_route');
    });

    it('does not require authentication', function () {
        $product = createRedirectionProduct();

        $this->getJson("/api/v2/products/{$product['id']}/test-pass/redirection-target")
            ->assertOk();
    });
});

describe('GET /v2/product/{id}/redirect/{password}', function () {
    it('returns 302 redirect to target URL for valid product', function () {
        $product = createRedirectionProduct(['target_url' => 'https://google.com']);

        $this->get("/v2/product/{$product['id']}/redirect/test-pass")
            ->assertRedirect('https://google.com');
    });

    it('returns 404 for non-existent product', function () {
        $this->get('/v2/product/999999/redirect/fake')
            ->assertNotFound();
    });

    it('returns 302 redirect to frontend for inactive product', function () {
        $userId = \App\Models\User::factory()->create()->id;
        $product = createRedirectionProduct([
            'active' => false,
            'user_id' => $userId,
            'configuration_status' => 'completed',
        ]);

        $this->get("/v2/product/{$product['id']}/redirect/test-pass")
            ->assertStatus(302);
    });

    it('does not break the legacy redirect route', function () {
        $response = $this->get('/product/1/redirect/test');

        expect($response->status())->not->toBe(500);
    });
});
