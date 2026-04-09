<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Src\Products\Domain\Ports\ProductUsagePort;

require_once __DIR__ . '/../../../Support/ProductRedirectionHelpers.php';

/**
 * These tests verify that ProductsServiceProvider injects the correct front URL
 * into ResolveProductRedirectionHandler depending on the app.v2_enabled flag.
 *
 * Strategy: create a virgin product and hit /v2/product/{id}/redirect/{password}.
 * A virgin product always redirects to `{frontUrl}/products/{id}/configure?password={pass}`.
 * This lets us assert the injected URL from the redirect response without making the
 * handler class aware of the flag.
 */

function setupUrlInjectionTestEnv(): void
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
    $this->seed(ProductConfigurationStatusSeeder::class);
    setupUrlInjectionTestEnv();
});

describe('ResolveProductRedirectionHandler URL injection', function () {
    it('injects legacy front URL when v2 is disabled', function () {
        config([
            'app.v2_enabled' => false,
            'services.products.front_url' => 'https://legacy.example.com',
            'services.products.v2_front_url' => 'https://v2.example.com',
        ]);

        $product = createRedirectionProduct([
            'user_id' => null,
            'target_url' => null,
            'configuration_status' => 'not-started',
            'active' => false,
            'password' => 'inject-legacy-pass',
        ]);

        // Virgin product redirects to {frontUrl}/products/{id}/configure?password={pass}
        $response = $this->get("/v2/product/{$product['id']}/redirect/inject-legacy-pass");

        $response->assertRedirect();
        expect($response->headers->get('Location'))
            ->toContain('https://legacy.example.com');
    });

    it('injects V2 front URL when v2 is enabled', function () {
        config([
            'app.v2_enabled' => true,
            'services.products.front_url' => 'https://legacy.example.com',
            'services.products.v2_front_url' => 'https://v2.example.com',
        ]);

        $product = createRedirectionProduct([
            'user_id' => null,
            'target_url' => null,
            'configuration_status' => 'not-started',
            'active' => false,
            'password' => 'inject-v2-pass',
        ]);

        // Virgin product redirects to {frontUrl}/products/{id}/configure?password={pass}
        $response = $this->get("/v2/product/{$product['id']}/redirect/inject-v2-pass");

        $response->assertRedirect();
        expect($response->headers->get('Location'))
            ->toContain('https://v2.example.com');
    });
});
