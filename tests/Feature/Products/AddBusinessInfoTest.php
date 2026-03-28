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
function createBusinessProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'B-TYPE-' . $uid,
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
function createBusinessProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createBusinessProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => 7,
        'model' => 'ModelA',
        'linked_to_product_id' => null,
        'password' => 'secret-pass',
        'target_url' => 'https://example.com',
        'usage' => 0,
        'name' => 'Product',
        'description' => null,
        'active' => true,
        'configuration_status' => ConfigurationStatus::TARGET_SET,
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

    $this->user = $this->create_user(['email' => 'business-product@example.com']);
    $this->actingAs($this->user, 'stateful-api');

    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);
});

describe('POST /api/v2/products/{id}/business', function () {
    it('returns 200, creates business record and moves status to business-set', function () {
        $productId = createBusinessProduct(['user_id' => $this->user->id]);

        $this->postJson("/api/v2/products/{$productId}/business", [
            'name' => 'My Business',
            'not_a_business' => false,
            'types' => ['restaurant' => true],
            'place_types' => ['bar' => true],
            'size' => 'M',
        ])
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.configurationStatus', ConfigurationStatus::BUSINESS_SET);

        expect(DB::table('product_business')->where('product_id', $productId)->count())->toBe(1);
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'configuration_status' => ConfigurationStatus::BUSINESS_SET,
        ]);
    });

    it('updates existing business record without creating duplicates', function () {
        $productId = createBusinessProduct(['user_id' => $this->user->id]);

        $this->postJson("/api/v2/products/{$productId}/business", [
            'name' => 'Initial Name',
            'types' => ['restaurant' => true],
        ])->assertOk();

        $this->postJson("/api/v2/products/{$productId}/business", [
            'name' => 'Updated Name',
            'types' => ['restaurant' => true, 'cafe' => true],
        ])
            ->assertOk()
            ->assertJsonPath('data.product.configurationStatus', ConfigurationStatus::BUSINESS_SET);

        expect(DB::table('product_business')->where('product_id', $productId)->count())->toBe(1);
        $this->assertDatabaseHas('product_business', [
            'product_id' => $productId,
            'name' => 'Updated Name',
        ]);
    });

    it('returns 422 when required types field is missing', function () {
        $productId = createBusinessProduct(['user_id' => $this->user->id]);

        $this->postJson("/api/v2/products/{$productId}/business", [
            'name' => 'Missing Types',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 404 when product does not exist', function () {
        $this->postJson('/api/v2/products/999999/business', [
            'types' => ['restaurant' => true],
        ])->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createBusinessProduct(['user_id' => $this->user->id]);
        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$productId}/business", [
            'types' => ['restaurant' => true],
        ])->assertStatus(401);
    });
});
