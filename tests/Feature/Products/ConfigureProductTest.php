<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Infrastructure\Persistence\NullProductUsageAdapter;

/**
 * @param array<string, mixed> $overrides
 */
function createConfigureProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'C-TYPE-' . $uid,
        'name' => 'Type ' . $uid,
        'image_ref' => 'TYPE-' . $uid,
        'primary_model' => 'google',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

/**
 * @param array<string, mixed> $overrides
 */
function createConfigurableFeatureProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createConfigureProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'google',
        'linked_to_product_id' => null,
        'password' => 'secret-pass',
        'target_url' => null,
        'usage' => 0,
        'name' => 'Product',
        'description' => null,
        'active' => true,
        'configuration_status' => ConfigurationStatus::ASSIGNED,
        'assigned_at' => now(),
        'size' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ], $overrides));
}

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(ProductConfigurationStatusSeeder::class);

    $this->user = $this->create_user(['email' => 'configure-product@example.com']);
    $this->actingAs($this->user, 'stateful-api');

    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);
});

describe('PUT /api/v2/products/{id}/configure', function () {
    it('returns 200 and auto-completes assigned simple products', function () {
        $productId = createConfigurableFeatureProduct(['configuration_status' => ConfigurationStatus::ASSIGNED]);

        $this->putJson("/api/v2/products/{$productId}/configure", ['target_url' => 'https://example.com/configured'])
            ->assertOk()
            ->assertJsonPath('data.product.targetUrl', 'https://example.com/configured')
            ->assertJsonPath('data.product.configurationStatus', ConfigurationStatus::COMPLETED);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'target_url' => 'https://example.com/configured',
            'configuration_status' => ConfigurationStatus::COMPLETED,
        ]);
    });

    it('returns 200 and keeps advanced status while updating target url', function () {
        $productId = createConfigurableFeatureProduct(['configuration_status' => ConfigurationStatus::BUSINESS_SET]);

        $this->putJson("/api/v2/products/{$productId}/configure", ['target_url' => 'https://example.com/new-business'])
            ->assertOk()
            ->assertJsonPath('data.product.targetUrl', 'https://example.com/new-business')
            ->assertJsonPath('data.product.configurationStatus', ConfigurationStatus::BUSINESS_SET);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'target_url' => 'https://example.com/new-business',
            'configuration_status' => ConfigurationStatus::BUSINESS_SET,
        ]);
    });

    it('returns 422 when target_url is invalid', function () {
        $productId = createConfigurableFeatureProduct();

        $this->putJson("/api/v2/products/{$productId}/configure", ['target_url' => 'not-a-url'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    });

    it('returns 404 when product does not exist', function () {
        $this->putJson('/api/v2/products/999999/configure', ['target_url' => 'https://example.com'])
            ->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createConfigurableFeatureProduct();
        auth()->guard('stateful-api')->logout();

        $this->putJson("/api/v2/products/{$productId}/configure", ['target_url' => 'https://example.com'])
            ->assertStatus(401);
    });
});
