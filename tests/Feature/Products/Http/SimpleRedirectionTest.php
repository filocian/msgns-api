<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Src\Products\Domain\Ports\ProductUsagePort;

function createRedirectionProductType(): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId([
        'code' => 'TYPE-' . $uid,
        'name' => 'Type ' . $uid,
        'image_ref' => 'TYPE-' . $uid,
        'primary_model' => 'google',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/**
 * @param array<string, mixed> $overrides
 * @return array{id: int, data: array<string, mixed>}
 */
function createRedirectionProduct(array $overrides = []): array
{
    $productTypeId = $overrides['product_type_id'] ?? createRedirectionProductType();
    $password = $overrides['password'] ?? 'test-pass';
    $defaults = [
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'google',
        'linked_to_product_id' => null,
        'password' => $password,
        'target_url' => 'https://google.com',
        'usage' => 0,
        'name' => 'Test Product',
        'description' => null,
        'active' => true,
        'configuration_status' => 'completed',
        'assigned_at' => null,
        'size' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ];

    $data = array_merge($defaults, $overrides);
    $id = DB::table('products')->insertGetId($data);

    return ['id' => $id, 'data' => $data];
}

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

    it('returns 422 for inactive product', function () {
        $product = createRedirectionProduct(['active' => false]);

        $this->getJson("/api/v2/products/{$product['id']}/test-pass/redirection-target")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'product_not_active');
    });

    it('returns 422 for product with incomplete configuration', function () {
        $product = createRedirectionProduct(['configuration_status' => 'assigned']);

        $this->getJson("/api/v2/products/{$product['id']}/test-pass/redirection-target")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'product_incomplete_configuration');
    });

    it('returns 422 for product with null target_url', function () {
        $product = createRedirectionProduct(['target_url' => null]);

        $this->getJson("/api/v2/products/{$product['id']}/test-pass/redirection-target")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'product_missing_target_url');
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

    it('returns 422 for inactive product', function () {
        $product = createRedirectionProduct(['active' => false]);

        $this->get("/v2/product/{$product['id']}/redirect/test-pass")
            ->assertStatus(422);
    });

    it('does not break the legacy redirect route', function () {
        $response = $this->get('/product/1/redirect/test');

        expect($response->status())->not->toBe(500);
    });
});
