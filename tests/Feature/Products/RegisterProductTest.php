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
function createRegisterProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'R-TYPE-' . $uid,
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
function createRegisterProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createRegisterProductType();

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
        'active' => false,
        'configuration_status' => ConfigurationStatus::NOT_STARTED,
        'assigned_at' => null,
        'size' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ], $overrides));
}

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(ProductConfigurationStatusSeeder::class);

    $this->user = $this->create_user(['email' => 'register-product@example.com']);
    $this->actingAs($this->user, 'stateful-api');

    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);

    $this->createRole('developer');
    $this->createRole('backoffice');
});

describe('POST /api/v2/products/{id}/register', function () {
    it('returns 200 and assigns/activates product with assigned status', function () {
        $productId = createRegisterProduct(['password' => 'my-secret']);

        $this->postJson("/api/v2/products/{$productId}/register", [
            'user_id' => $this->user->id,
            'password' => 'my-secret',
        ])
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.userId', $this->user->id)
            ->assertJsonPath('data.product.active', true)
            ->assertJsonPath('data.product.configurationStatus', ConfigurationStatus::ASSIGNED);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'user_id' => $this->user->id,
            'active' => true,
            'configuration_status' => ConfigurationStatus::ASSIGNED,
        ]);
    });

    it('returns 422 with invalid_product_password for wrong password', function () {
        $productId = createRegisterProduct(['password' => 'real-password']);

        $this->postJson("/api/v2/products/{$productId}/register", [
            'user_id' => $this->user->id,
            'password' => 'wrong-password',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_product_password');
    });

    it('returns 422 when required fields are missing', function () {
        $productId = createRegisterProduct();

        $this->postJson("/api/v2/products/{$productId}/register", [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    });

    it('returns 404 when product does not exist', function () {
        $this->postJson('/api/v2/products/999999/register', [
            'user_id' => $this->user->id,
            'password' => 'secret',
        ])->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createRegisterProduct();
        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$productId}/register", [
            'user_id' => $this->user->id,
            'password' => 'secret-pass',
        ])->assertStatus(401);
    });

    it('returns 403 when a regular user tries to register an already-owned product', function () {
        $owner = $this->create_user(['email' => 'existing-owner@example.com']);
        $productId = createRegisterProduct([
            'password' => 'my-secret',
            'user_id' => $owner->id,
        ]);

        $this->postJson("/api/v2/products/{$productId}/register", [
            'user_id' => $this->user->id,
            'password' => 'my-secret',
        ])->assertForbidden();
    });

    it('allows a backoffice admin to reassign an already-owned product', function () {
        $admin = $this->create_user(['email' => 'backoffice-register@example.com']);
        $admin->assignRole('backoffice');
        $this->actingAs($admin, 'stateful-api');

        $owner = $this->create_user(['email' => 'current-owner-register@example.com']);
        $productId = createRegisterProduct([
            'password' => 'my-secret',
            'user_id' => $owner->id,
        ]);

        $this->postJson("/api/v2/products/{$productId}/register", [
            'user_id' => $admin->id,
            'password' => 'my-secret',
        ])
            ->assertOk()
            ->assertJsonPath('data.product.userId', $admin->id);
    });

    it('allows a developer admin to reassign an already-owned product', function () {
        $admin = $this->create_user(['email' => 'developer-register@example.com']);
        $admin->assignRole('developer');
        $this->actingAs($admin, 'stateful-api');

        $owner = $this->create_user(['email' => 'current-owner-dev@example.com']);
        $productId = createRegisterProduct([
            'password' => 'my-secret',
            'user_id' => $owner->id,
        ]);

        $this->postJson("/api/v2/products/{$productId}/register", [
            'user_id' => $admin->id,
            'password' => 'my-secret',
        ])
            ->assertOk()
            ->assertJsonPath('data.product.userId', $admin->id);
    });
});
