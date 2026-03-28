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
function createGroupProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'G-TYPE-' . $uid,
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
function createGroupProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createGroupProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => 7,
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

    $this->user = $this->create_user(['email' => 'group-products@example.com']);
    $this->otherUser = $this->create_user(['email' => 'group-products-other@example.com']);
    $this->actingAs($this->user, 'stateful-api');

    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);
});

describe('POST /api/v2/products/{referenceId}/group/{candidateId}', function () {
    it('returns 200 and links compatible primary/candidate products', function () {
        $typeId = createGroupProductType(['primary_model' => 'ModelA', 'secondary_model' => 'ModelB']);

        $referenceId = createGroupProduct([
            'product_type_id' => $typeId,
            'user_id' => $this->user->id,
            'model' => 'ModelA',
        ]);
        $candidateId = createGroupProduct([
            'product_type_id' => $typeId,
            'user_id' => $this->user->id,
            'model' => 'ModelB',
        ]);

        $this->postJson("/api/v2/products/{$referenceId}/group/{$candidateId}")
            ->assertOk()
            ->assertJsonPath('data.product.id', $referenceId)
            ->assertJsonPath('data.product.linkedToProductId', $candidateId);

        $this->assertDatabaseHas('products', ['id' => $referenceId, 'linked_to_product_id' => $candidateId]);
    });

    it('returns 422 invalid_model_combination when reference is not primary model', function () {
        $typeId = createGroupProductType(['primary_model' => 'ModelA', 'secondary_model' => 'ModelB']);

        $referenceId = createGroupProduct(['product_type_id' => $typeId, 'user_id' => $this->user->id, 'model' => 'ModelB']);
        $candidateId = createGroupProduct(['product_type_id' => $typeId, 'user_id' => $this->user->id, 'model' => 'ModelA']);

        $this->postJson("/api/v2/products/{$referenceId}/group/{$candidateId}")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_model_combination');
    });

    it('returns 422 primary_product_already_linked when reference already has a link', function () {
        $typeId = createGroupProductType();
        $alreadyLinkedId = createGroupProduct(['product_type_id' => $typeId, 'user_id' => $this->user->id]);

        $referenceId = createGroupProduct([
            'product_type_id' => $typeId,
            'user_id' => $this->user->id,
            'model' => 'ModelA',
            'linked_to_product_id' => $alreadyLinkedId,
        ]);
        $candidateId = createGroupProduct(['product_type_id' => $typeId, 'user_id' => $this->user->id, 'model' => 'ModelB']);

        $this->postJson("/api/v2/products/{$referenceId}/group/{$candidateId}")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'primary_product_already_linked');
    });

    it('returns 422 secondary_product_already_linked when candidate already has a link', function () {
        $typeId = createGroupProductType();
        $existingPrimaryId = createGroupProduct(['product_type_id' => $typeId, 'user_id' => $this->user->id]);

        $referenceId = createGroupProduct(['product_type_id' => $typeId, 'user_id' => $this->user->id, 'model' => 'ModelA']);
        $candidateId = createGroupProduct([
            'product_type_id' => $typeId,
            'user_id' => $this->user->id,
            'model' => 'ModelB',
            'linked_to_product_id' => $existingPrimaryId,
        ]);

        $this->postJson("/api/v2/products/{$referenceId}/group/{$candidateId}")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'secondary_product_already_linked');
    });

    it('returns 422 products_must_have_same_type for different types', function () {
        $typeA = createGroupProductType(['primary_model' => 'ModelA', 'secondary_model' => 'ModelB']);
        $typeB = createGroupProductType(['primary_model' => 'ModelA', 'secondary_model' => 'ModelB']);

        $referenceId = createGroupProduct(['product_type_id' => $typeA, 'user_id' => $this->user->id, 'model' => 'ModelA']);
        $candidateId = createGroupProduct(['product_type_id' => $typeB, 'user_id' => $this->user->id, 'model' => 'ModelB']);

        $this->postJson("/api/v2/products/{$referenceId}/group/{$candidateId}")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'products_must_have_same_type');
    });

    it('returns 422 products_must_have_same_user for different owners', function () {
        $typeId = createGroupProductType();

        $referenceId = createGroupProduct(['product_type_id' => $typeId, 'user_id' => $this->user->id, 'model' => 'ModelA']);
        $candidateId = createGroupProduct(['product_type_id' => $typeId, 'user_id' => $this->otherUser->id, 'model' => 'ModelB']);

        $this->postJson("/api/v2/products/{$referenceId}/group/{$candidateId}")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'products_must_have_same_user');
    });

    it('returns 404 when reference product does not exist', function () {
        $candidateId = createGroupProduct(['user_id' => $this->user->id, 'model' => 'ModelB']);

        $this->postJson("/api/v2/products/999999/group/{$candidateId}")
            ->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $typeId = createGroupProductType();
        $referenceId = createGroupProduct(['product_type_id' => $typeId, 'user_id' => $this->user->id, 'model' => 'ModelA']);
        $candidateId = createGroupProduct(['product_type_id' => $typeId, 'user_id' => $this->user->id, 'model' => 'ModelB']);

        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$referenceId}/group/{$candidateId}")
            ->assertStatus(401);
    });
});
