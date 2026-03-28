<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

/**
 * @param array<string, mixed> $overrides
 */
function createActionProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'TYPE-' . $uid,
        'name' => 'Type ' . $uid,
        'image_ref' => 'TYPE-' . $uid,
        'primary_model' => 'ModelA',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

/**
 * @param array<string, mixed> $overrides
 */
function createActionProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createActionProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'Model-' . uniqid(),
        'linked_to_product_id' => null,
        'password' => 'secret-' . uniqid(),
        'target_url' => null,
        'usage' => 0,
        'name' => 'Product ' . uniqid(),
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
    $this->user = $this->create_user(['email' => 'product-actions@example.com']);
    $this->actingAs($this->user, 'stateful-api');
});

describe('PATCH /api/v2/products/{id}/assign', function () {
    it('returns 200 with data.product when assigning a product', function () {
        $productId = createActionProduct();

        $this->patchJson("/api/v2/products/{$productId}/assign", ['user_id' => $this->user->id])
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.userId', $this->user->id);

        $this->assertDatabaseHas('products', ['id' => $productId, 'user_id' => $this->user->id]);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createActionProduct();
        auth()->guard('stateful-api')->logout();

        $this->patchJson("/api/v2/products/{$productId}/assign", ['user_id' => 7])->assertStatus(401);
    });

    it('returns 404 when assigning a missing product', function () {
        $this->patchJson('/api/v2/products/999999/assign', ['user_id' => 7])->assertStatus(404);
    });

    it('returns 422 when user_id is missing', function () {
        $productId = createActionProduct();

        $this->patchJson("/api/v2/products/{$productId}/assign", [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });
});

describe('PATCH /api/v2/products/{id}/target-url', function () {
    it('returns 200 with data.product when setting the target url', function () {
        $productId = createActionProduct();

        $this->patchJson("/api/v2/products/{$productId}/target-url", ['target_url' => 'https://example.com'])
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.targetUrl', 'https://example.com');

        $this->assertDatabaseHas('products', ['id' => $productId, 'target_url' => 'https://example.com']);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createActionProduct();
        auth()->guard('stateful-api')->logout();

        $this->patchJson("/api/v2/products/{$productId}/target-url", ['target_url' => 'https://example.com'])->assertStatus(401);
    });

    it('returns 404 when setting target url on a missing product', function () {
        $this->patchJson('/api/v2/products/999999/target-url', ['target_url' => 'https://example.com'])->assertStatus(404);
    });

    it('returns 422 when target_url is invalid', function () {
        $productId = createActionProduct();

        $this->patchJson("/api/v2/products/{$productId}/target-url", ['target_url' => 'not-a-url'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });
});

describe('POST /api/v2/products/{id}/activate', function () {
    it('returns 200 with data.product when activating a product', function () {
        $productId = createActionProduct(['active' => false]);

        $this->postJson("/api/v2/products/{$productId}/activate")
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.active', true);

        $this->assertDatabaseHas('products', ['id' => $productId, 'active' => true]);
    });

    it('returns 200 when activating an already-active product', function () {
        $productId = createActionProduct(['active' => true]);

        $this->postJson("/api/v2/products/{$productId}/activate")
            ->assertOk()
            ->assertJsonPath('data.product.active', true);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createActionProduct();
        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$productId}/activate")->assertStatus(401);
    });

    it('returns 404 when activating a missing product', function () {
        $this->postJson('/api/v2/products/999999/activate')->assertStatus(404);
    });
});

describe('POST /api/v2/products/{id}/deactivate', function () {
    it('returns 200 with data.product when deactivating a product', function () {
        $productId = createActionProduct(['active' => true]);

        $this->postJson("/api/v2/products/{$productId}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.active', false);

        $this->assertDatabaseHas('products', ['id' => $productId, 'active' => false]);
    });

    it('returns 200 when deactivating an already-inactive product', function () {
        $productId = createActionProduct(['active' => false]);

        $this->postJson("/api/v2/products/{$productId}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.product.active', false);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createActionProduct();
        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$productId}/deactivate")->assertStatus(401);
    });

    it('returns 404 when deactivating a missing product', function () {
        $this->postJson('/api/v2/products/999999/deactivate')->assertStatus(404);
    });
});

describe('PATCH /api/v2/products/{id}/config-status', function () {
    it('returns 200 with data.product when changing the configuration status', function () {
        $productId = createActionProduct(['configuration_status' => ConfigurationStatus::NOT_STARTED]);

        $this->patchJson("/api/v2/products/{$productId}/config-status", ['status' => ConfigurationStatus::ASSIGNED])
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.configurationStatus', ConfigurationStatus::ASSIGNED);

        $this->assertDatabaseHas('products', ['id' => $productId, 'configuration_status' => ConfigurationStatus::ASSIGNED]);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createActionProduct();
        auth()->guard('stateful-api')->logout();

        $this->patchJson("/api/v2/products/{$productId}/config-status", ['status' => ConfigurationStatus::ASSIGNED])->assertStatus(401);
    });

    it('returns 404 when changing the status of a missing product', function () {
        $this->patchJson('/api/v2/products/999999/config-status', ['status' => ConfigurationStatus::ASSIGNED])->assertStatus(404);
    });

    it('returns 422 when status is missing', function () {
        $productId = createActionProduct();

        $this->patchJson("/api/v2/products/{$productId}/config-status", [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 422 for an invalid status string', function () {
        $productId = createActionProduct();

        $this->patchJson("/api/v2/products/{$productId}/config-status", ['status' => 'not_a_real_status'])
            ->assertStatus(422);
    });

    it('returns 422 for an invalid transition instead of 500', function () {
        $productId = createActionProduct(['configuration_status' => ConfigurationStatus::COMPLETED]);

        $this->patchJson("/api/v2/products/{$productId}/config-status", ['status' => ConfigurationStatus::ASSIGNED])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_configuration_status_transition');
    });
});

describe('PATCH /api/v2/products/{id}/name', function () {
    it('returns 200 with data.product when renaming a product', function () {
        $productId = createActionProduct(['name' => 'Old Name']);

        $this->patchJson("/api/v2/products/{$productId}/name", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.name', 'New Name');

        $this->assertDatabaseHas('products', ['id' => $productId, 'name' => 'New Name']);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createActionProduct();
        auth()->guard('stateful-api')->logout();

        $this->patchJson("/api/v2/products/{$productId}/name", ['name' => 'New Name'])->assertStatus(401);
    });

    it('returns 404 when renaming a missing product', function () {
        $this->patchJson('/api/v2/products/999999/name', ['name' => 'New Name'])->assertStatus(404);
    });

    it('returns 422 when name is missing', function () {
        $productId = createActionProduct();

        $this->patchJson("/api/v2/products/{$productId}/name", [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });
});

describe('DELETE /api/v2/products/{id}', function () {
    it('returns 204 with no body when soft deleting a product', function () {
        $productId = createActionProduct();

        $response = $this->deleteJson("/api/v2/products/{$productId}");

        $response->assertStatus(204);
        expect($response->getContent())->toBe('');

        $this->assertSoftDeleted('products', ['id' => $productId]);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createActionProduct();
        auth()->guard('stateful-api')->logout();

        $this->deleteJson("/api/v2/products/{$productId}")->assertStatus(401);
    });

    it('returns 404 when soft deleting a missing product', function () {
        $this->deleteJson('/api/v2/products/999999')->assertStatus(404);
    });

    it('returns 404 when soft deleting an already-deleted product', function () {
        $productId = createActionProduct(['deleted_at' => now()]);

        $this->deleteJson("/api/v2/products/{$productId}")->assertStatus(404);
    });
});

describe('POST /api/v2/products/{id}/restore', function () {
    it('returns 200 with data.product when restoring a soft-deleted product', function () {
        $productId = createActionProduct(['deleted_at' => now()]);

        $this->postJson("/api/v2/products/{$productId}/restore")
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.deletedAt', null);

        $this->assertDatabaseHas('products', ['id' => $productId, 'deleted_at' => null]);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createActionProduct(['deleted_at' => now()]);
        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$productId}/restore")->assertStatus(401);
    });

    it('returns 404 when restoring a truly missing product', function () {
        $this->postJson('/api/v2/products/999999/restore')->assertStatus(404);
    });
});

describe('DELETE /api/v2/products/{id}/link', function () {
    it('returns 200 with data.product when removing a product link', function () {
        $linkedId = createActionProduct();
        $productId = createActionProduct(['linked_to_product_id' => $linkedId]);

        $this->deleteJson("/api/v2/products/{$productId}/link")
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.linkedToProductId', null);

        $this->assertDatabaseHas('products', ['id' => $productId, 'linked_to_product_id' => null]);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createActionProduct();
        auth()->guard('stateful-api')->logout();

        $this->deleteJson("/api/v2/products/{$productId}/link")->assertStatus(401);
    });

    it('returns 404 when removing a link from a missing product', function () {
        $this->deleteJson('/api/v2/products/999999/link')->assertStatus(404);
    });
});
