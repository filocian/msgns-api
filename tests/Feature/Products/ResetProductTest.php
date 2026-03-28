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
function createResetProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'S-TYPE-' . $uid,
        'name' => 'Type ' . $uid,
        'image_ref' => 'TYPE-' . $uid,
        'primary_model' => 'S-GG-XX-RC',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

/**
 * @param array<string, mixed> $overrides
 */
function createResettableProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createResetProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'S-GG-XX-RC',
        'linked_to_product_id' => null,
        'password' => 'secret-' . uniqid(),
        'target_url' => 'https://example.com/reset-me',
        'usage' => 33,
        'name' => 'Configured Product',
        'description' => 'Before reset',
        'active' => true,
        'configuration_status' => ConfigurationStatus::TARGET_SET,
        'assigned_at' => now(),
        'size' => 'M',
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ], $overrides));
}

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(ProductConfigurationStatusSeeder::class);

    $this->user = $this->create_user(['email' => 'reset-action@example.com']);
    $this->actingAs($this->user, 'stateful-api');

    // Avoid real AWS/DynamoDB calls for feature tests.
    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);
});

describe('POST /api/v2/products/{id}/reset', function () {
    it('returns 200 and resets the product to virgin state', function () {
        $linkedProductId = createResettableProduct();
        $productId = createResettableProduct([
            'user_id' => $this->user->id,
            'linked_to_product_id' => $linkedProductId,
        ]);

        $this->postJson("/api/v2/products/{$productId}/reset")
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.userId', null)
            ->assertJsonPath('data.product.targetUrl', null)
            ->assertJsonPath('data.product.linkedToProductId', null)
            ->assertJsonPath('data.product.usage', 0)
            ->assertJsonPath('data.product.configurationStatus', ConfigurationStatus::NOT_STARTED)
            ->assertJsonPath('data.product.name', "S-GG-XX-RC ({$productId})");

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'user_id' => null,
            'target_url' => null,
            'linked_to_product_id' => null,
            'usage' => 0,
            'configuration_status' => ConfigurationStatus::NOT_STARTED,
            'name' => "S-GG-XX-RC ({$productId})",
            'assigned_at' => null,
        ]);
    });

    it('returns 404 when product does not exist', function () {
        $this->postJson('/api/v2/products/999999/reset')->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createResettableProduct(['user_id' => $this->user->id]);
        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$productId}/reset")->assertStatus(401);
    });

    it('returns 422 with product_type_not_resettable for bracelet product types', function () {
        $braceletTypeId = createResetProductType([
            'code' => 'B-GG-XX-RC',
            'primary_model' => 'B-GG-XX-RC',
        ]);

        $productId = createResettableProduct([
            'product_type_id' => $braceletTypeId,
            'user_id' => $this->user->id,
            'model' => 'S-GG-XX-RC', // Guard must use ProductType.code, not product.model
        ]);

        $this->postJson("/api/v2/products/{$productId}/reset")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'product_type_not_resettable')
            ->assertJsonPath('error.context.product_type_code', 'B-GG-XX-RC');
    });

    it('returns 422 with product_type_not_resettable for fancelet product types', function () {
        $fanceletTypeId = createResetProductType([
            'code' => 'F-GG-XX-RC',
            'primary_model' => 'F-GG-XX-RC',
        ]);

        $productId = createResettableProduct([
            'product_type_id' => $fanceletTypeId,
            'user_id' => $this->user->id,
            'model' => 'S-GG-XX-RC', // Guard must use ProductType.code, not product.model
        ]);

        $this->postJson("/api/v2/products/{$productId}/reset")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'product_type_not_resettable')
            ->assertJsonPath('error.context.product_type_code', 'F-GG-XX-RC');
    });
});
