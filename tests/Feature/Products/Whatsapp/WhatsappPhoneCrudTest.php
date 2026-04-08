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
function createPhoneCrudProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'PH-TYPE-' . $uid,
        'name' => 'Phone Type ' . $uid,
        'image_ref' => 'PH-TYPE-' . $uid,
        'primary_model' => 'whatsapp',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

/**
 * @param array<string, mixed> $overrides
 */
function createPhoneCrudProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createPhoneCrudProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'whatsapp',
        'linked_to_product_id' => null,
        'password' => 'secret-pass',
        'target_url' => null,
        'usage' => 0,
        'name' => 'Phone CRUD Product',
        'description' => null,
        'active' => true,
        'configuration_status' => ConfigurationStatus::COMPLETED,
        'assigned_at' => now(),
        'size' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ], $overrides));
}

function createPhoneCrudWhatsappPhone(int $productId, string $phone = '600000000', string $prefix = '+34'): int
{
    return DB::table('whatsapp_phones')->insertGetId([
        'product_id' => $productId,
        'phone' => $phone,
        'prefix' => $prefix,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(ProductConfigurationStatusSeeder::class);

    $this->user = $this->create_user(['email' => 'whatsapp-phone-crud@example.com']);
    $this->actingAs($this->user, 'stateful-api');

    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);
});

describe('POST /api/v2/products/{id}/whatsapp/phones', function () {
    it('adds a phone to a product', function () {
        $productId = createPhoneCrudProduct();

        $response = $this->postJson("/api/v2/products/{$productId}/whatsapp/phones", [
            'phone' => '611222333',
            'prefix' => '+34',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.phone.productId', $productId)
            ->assertJsonPath('data.phone.phone', '611222333')
            ->assertJsonPath('data.phone.prefix', '+34');

        $this->assertDatabaseHas('whatsapp_phones', [
            'product_id' => $productId,
            'phone' => '611222333',
            'prefix' => '+34',
        ]);
    });

    it('returns 404 when product does not exist', function () {
        $this->postJson('/api/v2/products/999999/whatsapp/phones', [
            'phone' => '611222333',
            'prefix' => '+34',
        ])->assertStatus(404);
    });

    it('returns 422 when required fields are missing', function () {
        $productId = createPhoneCrudProduct();

        $this->postJson("/api/v2/products/{$productId}/whatsapp/phones", [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createPhoneCrudProduct();
        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$productId}/whatsapp/phones", [
            'phone' => '611222333',
            'prefix' => '+34',
        ])->assertStatus(401);
    });
});

describe('DELETE /api/v2/products/{id}/whatsapp/phones/{phoneId}', function () {
    it('removes a phone when multiple phones exist', function () {
        $productId = createPhoneCrudProduct();
        $phone1Id = createPhoneCrudWhatsappPhone($productId, '600000001', '+34');
        $phone2Id = createPhoneCrudWhatsappPhone($productId, '600000002', '+34');

        $this->deleteJson("/api/v2/products/{$productId}/whatsapp/phones/{$phone1Id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('whatsapp_phones', ['id' => $phone1Id]);
        $this->assertDatabaseHas('whatsapp_phones', ['id' => $phone2Id]);
    });

    it('returns 409 when trying to delete the last phone (guardrail)', function () {
        $productId = createPhoneCrudProduct();
        $phoneId = createPhoneCrudWhatsappPhone($productId);

        $this->deleteJson("/api/v2/products/{$productId}/whatsapp/phones/{$phoneId}")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'whatsapp_last_phone');

        $this->assertDatabaseHas('whatsapp_phones', ['id' => $phoneId]);
    });

    it('returns 404 when phone does not exist', function () {
        $productId = createPhoneCrudProduct();

        $this->deleteJson("/api/v2/products/{$productId}/whatsapp/phones/999999")
            ->assertStatus(404);
    });

    it('returns 404 when phone belongs to a different product', function () {
        $productId1 = createPhoneCrudProduct();
        $productId2 = createPhoneCrudProduct();
        $phoneId = createPhoneCrudWhatsappPhone($productId2);

        $this->deleteJson("/api/v2/products/{$productId1}/whatsapp/phones/{$phoneId}")
            ->assertStatus(404);
    });

    it('returns 404 when product does not exist', function () {
        $this->deleteJson('/api/v2/products/999999/whatsapp/phones/1')
            ->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createPhoneCrudProduct();
        $phoneId = createPhoneCrudWhatsappPhone($productId);
        auth()->guard('stateful-api')->logout();

        $this->deleteJson("/api/v2/products/{$productId}/whatsapp/phones/{$phoneId}")
            ->assertStatus(401);
    });
});
