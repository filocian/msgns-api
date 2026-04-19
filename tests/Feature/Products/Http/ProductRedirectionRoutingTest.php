<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Products\Domain\Ports\ProductUsagePort;

require_once __DIR__ . '/../../../Support/ProductRedirectionHelpers.php';

/**
 * Helper to bind the no-op ProductUsagePort stub after a refreshApplication.
 */
function bindNoOpProductUsagePort(): void
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

/**
 * Re-run migrations and seed after refreshApplication so the in-memory DB is ready.
 */
function refreshDbAfterAppRefresh(): void
{
    Artisan::call('migrate');
    Artisan::call('db:seed', ['--class' => ProductConfigurationStatusSeeder::class]);
    bindNoOpProductUsagePort();
}

beforeEach(function () {
    $this->seed(ProductConfigurationStatusSeeder::class);
    bindNoOpProductUsagePort();
});

describe('Route flag switching — /product/{id}/redirect/{password}', function () {
    it('routes to legacy controller when flag is false', function () {
        $_ENV['APP_V2_ENABLED'] = 'false';
        $_SERVER['APP_V2_ENABLED'] = 'false';
        $this->refreshApplication();
        refreshDbAfterAppRefresh();

        // Legacy UC returns redirect()->away() for unknown product (resolveNotFoundUrl)
        $this->get('/product/99999/redirect/wrong-pass')
            ->assertRedirect();
    });

    it('routes to V2 controller when flag is true', function () {
        $_ENV['APP_V2_ENABLED'] = 'true';
        $_SERVER['APP_V2_ENABLED'] = 'true';
        $this->refreshApplication();
        refreshDbAfterAppRefresh();

        // V2 handler throws NotFound domain exception → JSON 404 with error code
        $this->get('/product/99999/redirect/wrong-pass')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'product_not_found');
    });
});

describe('Route flag switching — /nfc/{data}', function () {
    it('routes to legacy controller when flag is false', function () {
        $_ENV['APP_V2_ENABLED'] = 'false';
        $_SERVER['APP_V2_ENABLED'] = 'false';
        $this->refreshApplication();
        refreshDbAfterAppRefresh();

        // Legacy UC returns redirect()->away() for unknown product (resolveNotFoundUrl)
        $this->get('/nfc/99999&psw=wrong-pass')
            ->assertRedirect();
    });

    it('routes to V2 controller when flag is true', function () {
        $_ENV['APP_V2_ENABLED'] = 'true';
        $_SERVER['APP_V2_ENABLED'] = 'true';
        $this->refreshApplication();
        refreshDbAfterAppRefresh();

        // V2 nfcRedirect throws NotFound domain exception → JSON 404 with error code
        $this->get('/nfc/99999&psw=wrong-pass')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'product_not_found');
    });
});

describe('Permanent V2 route — always active regardless of flag', function () {
    it('/v2/product/{id}/redirect/{password} routes to V2 when flag is false', function () {
        $_ENV['APP_V2_ENABLED'] = 'false';
        $_SERVER['APP_V2_ENABLED'] = 'false';
        $this->refreshApplication();
        refreshDbAfterAppRefresh();

        $this->get('/v2/product/99999/redirect/wrong-pass')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'product_not_found');
    });

    it('/v2/product/{id}/redirect/{password} routes to V2 when flag is true', function () {
        $_ENV['APP_V2_ENABLED'] = 'true';
        $_SERVER['APP_V2_ENABLED'] = 'true';
        $this->refreshApplication();
        refreshDbAfterAppRefresh();

        $this->get('/v2/product/99999/redirect/wrong-pass')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'product_not_found');
    });
});
