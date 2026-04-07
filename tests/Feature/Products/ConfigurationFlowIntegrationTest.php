<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

beforeEach(function () {
    $this->seed(ProductConfigurationStatusSeeder::class);
    $this->user = $this->create_user(['email' => 'flow-tests@example.com']);
    $this->actingAs($this->user, 'stateful-api');
});

function createFlowProduct(int $userId, array $overrides = []): int
{
    $typeId = DB::table('product_types')->insertGetId([
        'code' => 'TYPE-' . uniqid(),
        'name' => 'Type ' . uniqid(),
        'image_ref' => 'TYPE-' . uniqid(),
        'primary_model' => 'google',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $typeId,
        'user_id' => $userId,
        'model' => 'google',
        'linked_to_product_id' => null,
        'password' => 'secret',
        'target_url' => null,
        'usage' => 0,
        'name' => 'Flow Product',
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

describe('Product configuration flow', function () {
    it('auto-completes simple products during configure', function () {
        $productId = createFlowProduct($this->user->id);

        $this->putJson("/api/v2/products/{$productId}/configure", ['target_url' => 'https://configured.example.com'])
            ->assertOk()
            ->assertJsonPath('data.product.configurationStatus', ConfigurationStatus::COMPLETED);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'configuration_status' => ConfigurationStatus::COMPLETED,
            'target_url' => 'https://configured.example.com',
        ]);
    });

    it('completes target-set products explicitly', function () {
        $productId = createFlowProduct($this->user->id, [
            'target_url' => 'https://configured.example.com',
            'configuration_status' => ConfigurationStatus::TARGET_SET,
        ]);

        $this->postJson("/api/v2/products/{$productId}/complete-configuration")
            ->assertOk()
            ->assertJsonPath('data.product.configurationStatus', ConfigurationStatus::COMPLETED);
    });
});
