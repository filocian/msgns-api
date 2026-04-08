<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\Cache;
use Src\Products\Domain\Ports\ProductUsagePort;

require_once __DIR__ . '/../../../Support/ProductRedirectionHelpers.php';

beforeEach(function () {
    $this->seed(ProductConfigurationStatusSeeder::class);

    config(['services.product.front_url' => 'https://test-front.example.com']);

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

describe('GET /api/v2/products/{id}/{password}/redirection-target — decision tree', function () {
    it('virgin product → 200 frontend_route to stepper url', function () {
        $product = createRedirectionProduct([
            'user_id' => null,
            'target_url' => null,
            'configuration_status' => 'not-started',
            'active' => false,
            'password' => 'virgin-pass',
        ]);

        $this->getJson("/api/v2/products/{$product['id']}/virgin-pass/redirection-target")
            ->assertOk()
            ->assertJsonPath('data.type', 'frontend_route')
            ->assertJsonFragment(['url' => "https://test-front.example.com/products/{$product['id']}/configure?password=virgin-pass"]);
    });

    it('disabled non-virgin product → 200 frontend_route to disabled url', function () {
        $userId = \App\Models\User::factory()->create()->id;
        $product = createRedirectionProduct([
            'user_id' => $userId,
            'target_url' => 'https://google.com',
            'configuration_status' => 'completed',
            'active' => false,
            'password' => 'disabled-pass',
        ]);

        $this->getJson("/api/v2/products/{$product['id']}/disabled-pass/redirection-target")
            ->assertOk()
            ->assertJsonPath('data.type', 'frontend_route')
            ->assertJsonFragment(['url' => "https://test-front.example.com/product/disabled?id={$product['id']}"]);
    });

    it('misconfigured cannot bypass → 200 frontend_route to stepper url', function () {
        $product = createRedirectionProduct([
            'user_id' => null,
            'target_url' => null,
            'configuration_status' => 'assigned',
            'active' => true,
            'password' => 'misconfig-pass',
        ]);

        $this->getJson("/api/v2/products/{$product['id']}/misconfig-pass/redirection-target")
            ->assertOk()
            ->assertJsonPath('data.type', 'frontend_route')
            ->assertJsonFragment(['url' => "https://test-front.example.com/products/{$product['id']}/configure?password=misconfig-pass"]);
    });

    it('misconfigured can bypass → 200 external_url with targetUrl', function () {
        $userId = \App\Models\User::factory()->create()->id;
        $product = createRedirectionProduct([
            'user_id' => $userId,
            'target_url' => 'https://google.com',
            'configuration_status' => 'target-set',
            'active' => true,
            'password' => 'bypass-pass',
        ]);

        $this->getJson("/api/v2/products/{$product['id']}/bypass-pass/redirection-target")
            ->assertOk()
            ->assertJsonPath('data.type', 'external_url')
            ->assertJsonPath('data.url', 'https://google.com');
    });

    it('virgin product → cache is NOT written', function () {
        Cache::flush();

        $product = createRedirectionProduct([
            'user_id' => null,
            'target_url' => null,
            'configuration_status' => 'not-started',
            'active' => false,
            'password' => 'virgin-cache-pass',
        ]);

        $this->getJson("/api/v2/products/{$product['id']}/virgin-cache-pass/redirection-target")
            ->assertOk();

        expect(Cache::has("products:redirection:{$product['id']}"))->toBeFalse();
    });

    it('disabled product → cache is NOT written', function () {
        Cache::flush();

        $userId = \App\Models\User::factory()->create()->id;
        $product = createRedirectionProduct([
            'user_id' => $userId,
            'target_url' => 'https://google.com',
            'configuration_status' => 'completed',
            'active' => false,
            'password' => 'disabled-cache-pass',
        ]);

        $this->getJson("/api/v2/products/{$product['id']}/disabled-cache-pass/redirection-target")
            ->assertOk();

        expect(Cache::has("products:redirection:{$product['id']}"))->toBeFalse();
    });

    it('returns 404 for non-existent product', function () {
        $this->getJson('/api/v2/products/999999/wrong-password/redirection-target')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'product_not_found');
    });

    it('completed product → cache IS written (regression guard)', function () {
        Cache::flush();

        $userId = \App\Models\User::factory()->create()->id;
        $product = createRedirectionProduct([
            'user_id' => $userId,
            'target_url' => 'https://google.com',
            'configuration_status' => 'completed',
            'active' => true,
            'password' => 'completed-pass',
        ]);

        $this->getJson("/api/v2/products/{$product['id']}/completed-pass/redirection-target")
            ->assertOk()
            ->assertJsonPath('data.type', 'external_url');

        expect(Cache::has("products:redirection:{$product['id']}"))->toBeTrue();
    });
});
