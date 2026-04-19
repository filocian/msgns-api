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
function createWhatsappConfigureProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'WA-TYPE-' . $uid,
        'name' => 'WA Type ' . $uid,
        'image_ref' => 'WA-TYPE-' . $uid,
        'primary_model' => 'whatsapp',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

/**
 * @param array<string, mixed> $overrides
 */
function createWhatsappConfigurableProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createWhatsappConfigureProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'whatsapp',
        'linked_to_product_id' => null,
        'password' => 'secret-pass',
        'target_url' => null,
        'usage' => 0,
        'name' => 'WhatsApp Product',
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

    $this->user = $this->create_user(['email' => 'whatsapp-configure@example.com']);
    $this->actingAs($this->user, 'stateful-api');

    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);
});

describe('POST /api/v2/products/{id}/whatsapp/configure', function () {
    it('configures a product as WhatsApp and advances configuration status', function () {
        $productId = createWhatsappConfigurableProduct();

        $response = $this->postJson("/api/v2/products/{$productId}/whatsapp/configure", [
            'phone' => '612345678',
            'prefix' => '+34',
            'message' => 'Hola, quiero informacion',
            'locale_code' => 'es_ES',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.product.id', $productId);

        // Verify phone was created
        $this->assertDatabaseHas('whatsapp_phones', [
            'product_id' => $productId,
            'phone' => '612345678',
            'prefix' => '+34',
        ]);

        // Verify message was created with default flag
        $this->assertDatabaseHas('whatsapp_messages', [
            'product_id' => $productId,
            'message' => 'Hola, quiero informacion',
            'default' => true,
        ]);

        // Verify configuration status advanced (whatsapp skips BUSINESS_SET: ASSIGNED -> TARGET_SET -> COMPLETED)
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'configuration_status' => ConfigurationStatus::COMPLETED,
        ]);
    });

    it('returns 404 when product does not exist', function () {
        $this->postJson('/api/v2/products/999999/whatsapp/configure', [
            'phone' => '612345678',
            'prefix' => '+34',
            'message' => 'Hello',
            'locale_code' => 'en_US',
        ])->assertStatus(404);
    });

    it('returns 422 when locale_code is invalid', function () {
        $productId = createWhatsappConfigurableProduct();

        $this->postJson("/api/v2/products/{$productId}/whatsapp/configure", [
            'phone' => '612345678',
            'prefix' => '+34',
            'message' => 'Hello',
            'locale_code' => 'INVALID_LOCALE',
        ])->assertStatus(422);
    });

    it('returns 422 when required fields are missing', function () {
        $productId = createWhatsappConfigurableProduct();

        $this->postJson("/api/v2/products/{$productId}/whatsapp/configure", [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createWhatsappConfigurableProduct();
        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$productId}/whatsapp/configure", [
            'phone' => '612345678',
            'prefix' => '+34',
            'message' => 'Hello',
            'locale_code' => 'en_US',
        ])->assertStatus(401);
    });
});
