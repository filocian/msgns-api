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
function createCloneProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'CL-TYPE-' . $uid,
        'name' => 'Type ' . $uid,
        'image_ref' => 'TYPE-' . $uid,
        'primary_model' => 'ModelA',
        'secondary_model' => 'ModelB',
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

/**
 * @param array<string, mixed> $overrides
 */
function createCloneProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createCloneProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'ModelA',
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

    $this->user = $this->create_user(['email' => 'clone-product@example.com']);
    $this->actingAs($this->user, 'stateful-api');

    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);
});

describe('POST /api/v2/products/{id}/clone-from/{sourceId}', function () {
    it('returns 200 and clones source targetUrl/status into target', function () {
        $typeId = createCloneProductType();

        $targetId = createCloneProduct([
            'product_type_id' => $typeId,
            'target_url' => 'https://old.example.com',
            'configuration_status' => ConfigurationStatus::ASSIGNED,
        ]);
        $sourceId = createCloneProduct([
            'product_type_id' => $typeId,
            'target_url' => 'https://new.example.com',
            'configuration_status' => ConfigurationStatus::BUSINESS_SET,
        ]);

        $this->postJson("/api/v2/products/{$targetId}/clone-from/{$sourceId}")
            ->assertOk()
            ->assertJsonPath('data.product.id', $targetId)
            ->assertJsonPath('data.product.targetUrl', 'https://new.example.com')
            ->assertJsonPath('data.product.configurationStatus', ConfigurationStatus::BUSINESS_SET);

        $this->assertDatabaseHas('products', [
            'id' => $targetId,
            'target_url' => 'https://new.example.com',
            'configuration_status' => ConfigurationStatus::BUSINESS_SET,
        ]);
    });

    it('returns 422 with products_must_have_same_type for different types', function () {
        $targetType = createCloneProductType();
        $sourceType = createCloneProductType();

        $targetId = createCloneProduct(['product_type_id' => $targetType]);
        $sourceId = createCloneProduct(['product_type_id' => $sourceType]);

        $this->postJson("/api/v2/products/{$targetId}/clone-from/{$sourceId}")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'products_must_have_same_type');
    });

    it('returns 404 when source product does not exist', function () {
        $targetId = createCloneProduct();

        $this->postJson("/api/v2/products/{$targetId}/clone-from/999999")
            ->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $targetId = createCloneProduct();
        $sourceId = createCloneProduct();

        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$targetId}/clone-from/{$sourceId}")
            ->assertStatus(401);
    });
});
