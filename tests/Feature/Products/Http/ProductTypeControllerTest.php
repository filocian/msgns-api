<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

// ─── Helper ────────────────────────────────────────────────────────────────────

/**
 * Insert a product_type row directly and return its id.
 *
 * @param array<string, mixed> $overrides
 */
function createProductType(array $overrides = []): int
{
    $uid  = uniqid();
    $code = $overrides['code'] ?? 'TYPE-' . $uid;
    $defaults = [
        'code'            => $code,
        'name'            => 'Test Type ' . $uid,   // unique per call to avoid unique-constraint violations
        'description'     => null,
        'image_ref'       => $code,                 // image_ref is NOT NULL — mirror code as default
        'primary_model'   => 'ModelA',
        'secondary_model' => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ];

    return DB::table('product_types')->insertGetId(
        array_merge($defaults, $overrides)
    );
}

/**
 * Insert a fancelet_content_gallery row referencing a product_type, making it "in use".
 *
 * Using fancelet_content_gallery instead of products to avoid the complex FK/NOT NULL
 * constraints on the products table — both tables are checked by EloquentProductTypeUsageAdapter.
 */
function linkProductToType(int $productTypeId): void
{
    DB::table('fancelet_content_gallery')->insert([
        'product_type_id' => $productTypeId,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);
}

// ─── Setup ─────────────────────────────────────────────────────────────────────

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->user = $this->create_user(['email' => 'backoffice@example.com']);
    $this->actingAs($this->user, 'stateful-api');
});

// ═══════════════════════════════════════════════════════════════════════════════
// GET /api/v2/products/product-types  (AC-001)
// ═══════════════════════════════════════════════════════════════════════════════

describe('GET /api/v2/products/product-types', function () {

    it('returns 200 with paginated product types for an authenticated user (AC-001)', function () {
        createProductType(['code' => 'A001', 'name' => 'Type A']);
        createProductType(['code' => 'B002', 'name' => 'Type B']);

        $response = $this->getJson('/api/v2/products/product-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'code', 'name', 'primaryModel', 'secondaryModel', 'createdAt', 'updatedAt']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        expect($response->json('meta.current_page'))->toBe(1);
    });

    it('returns 401 for unauthenticated requests', function () {
        auth()->guard('stateful-api')->logout();
        $response = $this->getJson('/api/v2/products/product-types');

        $response->assertStatus(401);
    });

    it('returns empty list when there are no product types', function () {
        $response = $this->getJson('/api/v2/products/product-types');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    });

    it('supports pagination via per_page and page query params', function () {
        createProductType(['code' => 'P001']);
        createProductType(['code' => 'P002']);
        createProductType(['code' => 'P003']);

        $response = $this->getJson('/api/v2/products/product-types?per_page=2&page=1');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(2, 'data');
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// GET /api/v2/products/product-types/{id}  (AC-002)
// ═══════════════════════════════════════════════════════════════════════════════

describe('GET /api/v2/products/product-types/{id}', function () {

    it('returns 200 with full product type data for an existing id (AC-002)', function () {
        $id = createProductType([
            'code'            => 'DETAIL-CODE',
            'name'            => 'Detail Type',
            'primary_model'   => 'ModelX',
            'secondary_model' => 'ModelY',
        ]);

        $response = $this->getJson("/api/v2/products/product-types/{$id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.code', 'DETAIL-CODE')
            ->assertJsonPath('data.name', 'Detail Type')
            ->assertJsonPath('data.primaryModel', 'ModelX')
            ->assertJsonPath('data.secondaryModel', 'ModelY');
    });

    it('returns 404 for a non-existent product type id', function () {
        $response = $this->getJson('/api/v2/products/product-types/999999');

        $response->assertStatus(404);
    });

    it('returns 401 for an unauthenticated request', function () {
        $id = createProductType();

        auth()->guard('stateful-api')->logout();
        $response = $this->getJson("/api/v2/products/product-types/{$id}");

        $response->assertStatus(401);
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// POST /api/v2/products/product-types  (AC-003)
// ═══════════════════════════════════════════════════════════════════════════════

describe('POST /api/v2/products/product-types', function () {

    it('creates a product type and returns 201 with the persisted resource (AC-003)', function () {
        $response = $this->postJson('/api/v2/products/product-types', [
            'code'            => 'NEW-CODE',
            'name'            => 'New Product Type',
            'primary_model'   => 'ModelA',
            'secondary_model' => null,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'NEW-CODE')
            ->assertJsonPath('data.name', 'New Product Type')
            ->assertJsonPath('data.primaryModel', 'ModelA')
            ->assertJsonPath('data.secondaryModel', null);

        $this->assertDatabaseHas('product_types', [
            'code'  => 'NEW-CODE',
            'name'  => 'New Product Type',
        ]);
    });

    it('creates a product type with a secondary model', function () {
        $response = $this->postJson('/api/v2/products/product-types', [
            'code'            => 'WITH-SECONDARY',
            'name'            => 'Has Secondary',
            'primary_model'   => 'PrimModel',
            'secondary_model' => 'SecModel',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.secondaryModel', 'SecModel');
    });

    it('returns 400 when code is missing (FormRequest validation)', function () {
        $response = $this->postJson('/api/v2/products/product-types', [
            'name'          => 'No Code',
            'primary_model' => 'ModelX',
        ]);

        // The project maps Laravel ValidationException → 400 (see bootstrap/app.php)
        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 400 when name is missing (FormRequest validation)', function () {
        $response = $this->postJson('/api/v2/products/product-types', [
            'code'          => 'NO-NAME',
            'primary_model' => 'ModelX',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 400 when primary_model is missing (FormRequest validation)', function () {
        $response = $this->postJson('/api/v2/products/product-types', [
            'code' => 'NO-MODEL',
            'name' => 'No Model',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 400 when code exceeds 60 characters (FormRequest validation)', function () {
        $response = $this->postJson('/api/v2/products/product-types', [
            'code'          => str_repeat('A', 61),
            'name'          => 'Long Code',
            'primary_model' => 'ModelX',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 401 for an unauthenticated request', function () {
        auth()->guard('stateful-api')->logout();

        $response = $this->postJson('/api/v2/products/product-types', [
            'code'          => 'AUTH-TEST',
            'name'          => 'Auth Test',
            'primary_model' => 'ModelX',
        ]);

        $response->assertStatus(401);
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// PATCH /api/v2/products/product-types/{id}  (AC-004 through AC-007)
// ═══════════════════════════════════════════════════════════════════════════════

describe('PATCH /api/v2/products/product-types/{id}', function () {

    // ─── AC-004: Update protected fields when NOT in use ──────────────────────

    it('updates code, primary_model and secondary_model when NOT in use (AC-004)', function () {
        $id = createProductType([
            'code'            => 'OLD-CODE',
            'name'            => 'Old Name',
            'primary_model'   => 'OldModel',
            'secondary_model' => null,
        ]);

        $response = $this->patchJson("/api/v2/products/product-types/{$id}", [
            'code'            => 'NEW-CODE',
            'name'            => 'New Name',
            'primary_model'   => 'NewModel',
            'secondary_model' => 'SecModel',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.code', 'NEW-CODE')
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.primaryModel', 'NewModel')
            ->assertJsonPath('data.secondaryModel', 'SecModel');

        $this->assertDatabaseHas('product_types', [
            'id'              => $id,
            'code'            => 'NEW-CODE',
            'primary_model'   => 'NewModel',
            'secondary_model' => 'SecModel',
        ]);
    });

    // ─── AC-005: Update only name when in use ─────────────────────────────────

    it('allows updating only name when the type IS in use (AC-005)', function () {
        $id = createProductType([
            'code'          => 'PROTECTED-CODE',
            'name'          => 'Original Name',
            'primary_model' => 'OriginalModel',
        ]);
        linkProductToType($id);

        $response = $this->patchJson("/api/v2/products/product-types/{$id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.code', 'PROTECTED-CODE')
            ->assertJsonPath('data.primaryModel', 'OriginalModel');

        $this->assertDatabaseHas('product_types', [
            'id'            => $id,
            'name'          => 'Updated Name',
            'code'          => 'PROTECTED-CODE',
            'primary_model' => 'OriginalModel',
        ]);
    });

    // ─── AC-006: Reject code change when in use ───────────────────────────────

    it('returns 422 when attempting to change code on an in-use product type (AC-006)', function () {
        $id = createProductType([
            'code'          => 'IMMUTABLE-CODE',
            'name'          => 'In-Use Type',
            'primary_model' => 'FixedModel',
        ]);
        linkProductToType($id);

        $response = $this->patchJson("/api/v2/products/product-types/{$id}", [
            'code' => 'CHANGED-CODE',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'protected_fields_immutable');

        // The stored record must remain unchanged
        $this->assertDatabaseHas('product_types', [
            'id'   => $id,
            'code' => 'IMMUTABLE-CODE',
        ]);
    });

    it('returns 422 with fields context listing code when rejected (AC-006)', function () {
        $id = createProductType([
            'code'          => 'LOCKED-CODE',
            'name'          => 'In-Use Type',
            'primary_model' => 'ModelA',
        ]);
        linkProductToType($id);

        $response = $this->patchJson("/api/v2/products/product-types/{$id}", [
            'code' => 'UNLOCKED-CODE',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'protected_fields_immutable')
            ->assertJsonPath('error.context.reason', 'product_type_in_use');

        $fields = $response->json('error.context.fields');
        expect($fields)->toContain('code');
    });

    // ─── AC-007: Reject primary_model / secondary_model changes when in use ──

    it('returns 422 when attempting to change primary_model on an in-use product type (AC-007)', function () {
        $id = createProductType([
            'code'          => 'CODE-X',
            'name'          => 'In-Use Type',
            'primary_model' => 'OriginalPrimary',
        ]);
        linkProductToType($id);

        $response = $this->patchJson("/api/v2/products/product-types/{$id}", [
            'primary_model' => 'ChangedPrimary',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'protected_fields_immutable');

        $this->assertDatabaseHas('product_types', [
            'id'            => $id,
            'primary_model' => 'OriginalPrimary',
        ]);
    });

    it('returns 422 when attempting to change secondary_model on an in-use product type (AC-007)', function () {
        $id = createProductType([
            'code'            => 'CODE-Y',
            'name'            => 'In-Use Type',
            'primary_model'   => 'PrimaryA',
            'secondary_model' => 'OriginalSecondary',
        ]);
        linkProductToType($id);

        $response = $this->patchJson("/api/v2/products/product-types/{$id}", [
            'secondary_model' => 'ChangedSecondary',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'protected_fields_immutable');

        $this->assertDatabaseHas('product_types', [
            'id'              => $id,
            'secondary_model' => 'OriginalSecondary',
        ]);
    });

    it('returns 422 with all changed protected fields in context (AC-007)', function () {
        $id = createProductType([
            'code'            => 'MULTI-CODE',
            'name'            => 'Multi Protected',
            'primary_model'   => 'PrimaryX',
            'secondary_model' => 'SecondaryX',
        ]);
        linkProductToType($id);

        $response = $this->patchJson("/api/v2/products/product-types/{$id}", [
            'code'            => 'DIFFERENT-CODE',
            'primary_model'   => 'PrimaryY',
            'secondary_model' => 'SecondaryY',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'protected_fields_immutable');

        $fields = $response->json('error.context.fields');
        expect($fields)->toContain('code')
            ->and($fields)->toContain('primary_model')
            ->and($fields)->toContain('secondary_model');
    });

    // ─── Sad paths ────────────────────────────────────────────────────────────

    it('returns 404 when the product type does not exist', function () {
        $response = $this->patchJson('/api/v2/products/product-types/999999', [
            'name' => 'Ghost',
        ]);

        $response->assertStatus(404);
    });

    it('returns 401 for an unauthenticated request', function () {
        $id = createProductType();

        auth()->guard('stateful-api')->logout();
        $response = $this->patchJson("/api/v2/products/product-types/{$id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(401);
    });

    it('returns 400 when code exceeds 60 characters (FormRequest validation)', function () {
        $id = createProductType();

        $response = $this->patchJson("/api/v2/products/product-types/{$id}", [
            'code' => str_repeat('X', 61),
        ]);

        // FormRequest validation → 400 in this project
        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error');
    });

    // ─── No delete (AC-008) ───────────────────────────────────────────────────

    it('returns 405 Method Not Allowed for DELETE on the collection (AC-008)', function () {
        $response = $this->deleteJson('/api/v2/products/product-types');

        $response->assertStatus(405);
    });

    it('returns 405 Method Not Allowed for DELETE on a single resource (AC-008)', function () {
        $id = createProductType();

        $response = $this->deleteJson("/api/v2/products/product-types/{$id}");

        $response->assertStatus(405);
    });
});
