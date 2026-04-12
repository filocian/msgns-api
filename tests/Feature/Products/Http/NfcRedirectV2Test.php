<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Src\Products\Domain\Ports\ProductUsagePort;

require_once __DIR__ . '/../../../Support/ProductRedirectionHelpers.php';

/**
 * Helper to bind no-op ProductUsagePort and run migrations after refreshApplication.
 */
function setupNfcV2TestEnv(): void
{
    app()->bind(ProductUsagePort::class, static fn (): ProductUsagePort => new class implements ProductUsagePort
    {
        public function writeUsageEvent(int $productId, int $userId, string $productName, DateTimeImmutable $timestamp): void {}

        public function queryProductUsage(int $productId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
        {
            return [];
        }

        public function deleteProductUsage(int $productId): void {}
    });
}

beforeEach(function () {
    $_ENV['APP_V2_ENABLED'] = 'true';
    $_SERVER['APP_V2_ENABLED'] = 'true';
    $this->refreshApplication();
    Artisan::call('migrate');
    Artisan::call('db:seed', ['--class' => ProductConfigurationStatusSeeder::class]);
    setupNfcV2TestEnv();
    config(['services.products.front_url' => 'https://test-front.example.com']);
    config(['services.products.v2_front_url' => 'https://test-front-v2.example.com']);
});

afterEach(function () {
    $_ENV['APP_V2_ENABLED'] = 'false';
    $_SERVER['APP_V2_ENABLED'] = 'false';
});

describe('V2 NFC redirect — parsing', function () {
    it('returns 404 when product does not exist using encoded segment format', function () {
        $this->get('/nfc/99999&psw=wrong-pass')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'product_not_found');
    });

    it('returns 404 when product does not exist using query param format', function () {
        $this->get('/nfc/99999?psw=wrong-pass')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'product_not_found');
    });

    it('returns 404 when data segment has no parseable password', function () {
        $this->get('/nfc/99999')
            ->assertNotFound();
    });

    it('returns 404 when data segment has wrong param name', function () {
        $this->get('/nfc/99999&foo=wrong-pass')
            ->assertNotFound();
    });
});

describe('V2 NFC redirect — successful resolution', function () {
    it('parses encoded segment format and redirects for completed product', function () {
        $userId = \App\Models\User::factory()->create()->id;
        $product = createRedirectionProduct([
            'user_id' => $userId,
            'target_url' => 'https://google.com',
            'configuration_status' => 'completed',
            'active' => true,
            'password' => 'nfc-pass',
        ]);

        $this->get("/nfc/{$product['id']}&psw=nfc-pass")
            ->assertRedirect('https://google.com');
    });

    it('parses query param format and redirects for completed product', function () {
        $userId = \App\Models\User::factory()->create()->id;
        $product = createRedirectionProduct([
            'user_id' => $userId,
            'target_url' => 'https://google.com',
            'configuration_status' => 'completed',
            'active' => true,
            'password' => 'nfc-pass-q',
        ]);

        $this->get("/nfc/{$product['id']}?psw=nfc-pass-q")
            ->assertRedirect('https://google.com');
    });

    it('parses encoded segment format and redirects to frontend for virgin product', function () {
        $product = createRedirectionProduct([
            'user_id' => null,
            'target_url' => null,
            'configuration_status' => 'not-started',
            'active' => false,
            'password' => 'nfc-virgin',
        ]);

        $this->get("/nfc/{$product['id']}&psw=nfc-virgin")
            ->assertRedirect();
    });
});
