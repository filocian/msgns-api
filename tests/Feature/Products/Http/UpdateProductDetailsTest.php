<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

/**
 * @param array<string, mixed> $overrides
 */
function createDetailsProductType(array $overrides = []): int
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
function createDetailsProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createDetailsProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'Model-' . uniqid(),
        'linked_to_product_id' => null,
        'password' => 'secret-' . uniqid(),
        'target_url' => null,
        'usage' => 0,
        'name' => 'Original Name',
        'description' => 'Original description',
        'active' => false,
        'configuration_status' => ConfigurationStatus::NOT_STARTED,
        'assigned_at' => null,
        'size' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ], $overrides));
}

beforeEach(function (): void {
    $this->seed(ProductConfigurationStatusSeeder::class);

    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $this->createRole('developer');
    $this->createRole('backoffice');

    $this->owner = $this->create_user(['email' => 'owner-details@example.com']);
    $this->otherUser = $this->create_user(['email' => 'other-details@example.com']);
    $this->developer = $this->create_user(['email' => 'developer-details@example.com']);
    $this->backoffice = $this->create_user(['email' => 'backoffice-details@example.com']);

    $this->developer->assignRole('developer');
    $this->backoffice->assignRole('backoffice');
});

describe('PATCH /api/v2/products/{id}/details', function () {
    it('updates only name when description is omitted', function (): void {
        $productId = createDetailsProduct([
            'user_id' => $this->owner->id,
            'name' => 'Before Name',
            'description' => 'Before description',
        ]);

        $this->actingAs($this->owner, 'stateful-api')
            ->patchJson("/api/v2/products/{$productId}/details", [
                'name' => 'After Name',
            ])
            ->assertOk()
            ->assertJsonPath('data.product.id', $productId)
            ->assertJsonPath('data.product.name', 'After Name')
            ->assertJsonPath('data.product.description', 'Before description');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'After Name',
            'description' => 'Before description',
        ]);
    });

    it('updates only description when name is omitted', function (): void {
        $productId = createDetailsProduct([
            'user_id' => $this->owner->id,
            'name' => 'Before Name',
            'description' => 'Before description',
        ]);

        $this->actingAs($this->owner, 'stateful-api')
            ->patchJson("/api/v2/products/{$productId}/details", [
                'description' => 'After description',
            ])
            ->assertOk()
            ->assertJsonPath('data.product.name', 'Before Name')
            ->assertJsonPath('data.product.description', 'After description');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Before Name',
            'description' => 'After description',
        ]);
    });

    it('updates both name and description in one request', function (): void {
        $productId = createDetailsProduct([
            'user_id' => $this->owner->id,
            'name' => 'Before Name',
            'description' => 'Before description',
        ]);

        $this->actingAs($this->owner, 'stateful-api')
            ->patchJson("/api/v2/products/{$productId}/details", [
                'name' => 'After Name',
                'description' => 'After description',
            ])
            ->assertOk()
            ->assertJsonPath('data.product.name', 'After Name')
            ->assertJsonPath('data.product.description', 'After description');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'After Name',
            'description' => 'After description',
        ]);
    });

    it('clears description when description is null', function (): void {
        $productId = createDetailsProduct([
            'user_id' => $this->owner->id,
            'description' => 'To clear',
        ]);

        $this->actingAs($this->owner, 'stateful-api')
            ->patchJson("/api/v2/products/{$productId}/details", [
                'description' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.product.description', null);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'description' => null,
        ]);
    });

    it('returns 422 when payload does not include name or description', function (): void {
        $productId = createDetailsProduct([
            'user_id' => $this->owner->id,
            'name' => 'Before Name',
            'description' => 'Before description',
        ]);

        $this->actingAs($this->owner, 'stateful-api')
            ->patchJson("/api/v2/products/{$productId}/details", [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Before Name',
            'description' => 'Before description',
        ]);
    });

    it('returns 422 and persists nothing when one provided field is invalid', function (): void {
        $productId = createDetailsProduct([
            'user_id' => $this->owner->id,
            'name' => 'Before Name',
            'description' => 'Before description',
        ]);

        $this->actingAs($this->owner, 'stateful-api')
            ->patchJson("/api/v2/products/{$productId}/details", [
                'name' => 'After Name',
                'description' => str_repeat('A', 501),
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Before Name',
            'description' => 'Before description',
        ]);
    });

    it('allows a product owner to update own product details', function (): void {
        $productId = createDetailsProduct(['user_id' => $this->owner->id]);

        $this->actingAs($this->owner, 'stateful-api')
            ->patchJson("/api/v2/products/{$productId}/details", ['name' => 'Owned product'])
            ->assertOk();
    });

    it('allows developer role to update a product from another owner', function (): void {
        $productId = createDetailsProduct(['user_id' => $this->owner->id]);

        $this->actingAs($this->developer, 'stateful-api')
            ->patchJson("/api/v2/products/{$productId}/details", ['name' => 'Updated by developer'])
            ->assertOk()
            ->assertJsonPath('data.product.name', 'Updated by developer');
    });

    it('allows backoffice role to update a product from another owner', function (): void {
        $productId = createDetailsProduct(['user_id' => $this->owner->id]);

        $this->actingAs($this->backoffice, 'stateful-api')
            ->patchJson("/api/v2/products/{$productId}/details", ['name' => 'Updated by backoffice'])
            ->assertOk()
            ->assertJsonPath('data.product.name', 'Updated by backoffice');
    });

    it('returns 403 for authenticated non-owner without elevated role', function (): void {
        $productId = createDetailsProduct(['user_id' => $this->owner->id]);

        $this->actingAs($this->otherUser, 'stateful-api')
            ->patchJson("/api/v2/products/{$productId}/details", ['name' => 'No permission'])
            ->assertStatus(403);

        $this->assertDatabaseMissing('products', [
            'id' => $productId,
            'name' => 'No permission',
        ]);
    });

    it('returns 404 when product is missing', function (): void {
        $this->actingAs($this->developer, 'stateful-api')
            ->patchJson('/api/v2/products/999999/details', ['name' => 'Missing'])
            ->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function (): void {
        $productId = createDetailsProduct(['user_id' => $this->owner->id]);

        $this->patchJson("/api/v2/products/{$productId}/details", ['name' => 'No auth'])
            ->assertStatus(401);
    });

    it('does not expose this capability in legacy /api/products routes', function (): void {
        $productId = createDetailsProduct(['user_id' => $this->owner->id]);

        $response = $this->actingAs($this->owner, 'stateful-api')
            ->patchJson("/api/products/{$productId}/details", ['name' => 'Legacy']);

        expect($response->status())->toBeIn([404, 405]);
    });
});
