<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\DB;
use Src\Identity\Domain\Permissions\DomainPermissions;

// ─── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Insert a product_type row and return its id.
 *
 * @param array<string, mixed> $overrides
 */
function createGenerateProductType(array $overrides = []): int
{
    $uid = uniqid();
    $code = $overrides['code'] ?? 'GEN-' . $uid;

    return DB::table('product_types')->insertGetId(array_merge([
        'code'            => $code,
        'name'            => 'Gen Type ' . $uid,
        'description'     => null,
        'image_ref'       => $code,
        'primary_model'   => 'P-PRIMARY',
        'secondary_model' => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ], $overrides));
}

// ─── Setup ─────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(ProductConfigurationStatusSeeder::class);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $this->user = $this->create_user(['email' => 'generator@example.com']);

    // Grant the product_generation permission
    $permission = $this->createPermission(DomainPermissions::PRODUCT_GENERATION);
    $this->user->givePermissionTo($permission);

    $this->actingAs($this->user, 'stateful-api');
});

// ═══════════════════════════════════════════════════════════════════════════════
// POST /api/v2/products/generate
// ═══════════════════════════════════════════════════════════════════════════════

describe('POST /api/v2/products/generate', function () {

    // ─── 200 Excel (default) ──────────────────────────────────────────────────

    it('returns 200 with Excel file for valid single-model type (AC default)', function () {
        $typeId = createGenerateProductType([
            'primary_model' => 'P-SINGLE',
            'secondary_model' => null,
        ]);

        // Use json() to send JSON body but with Excel Accept header
        $response = $this->json('POST', '/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 3]],
        ], [
            'Accept' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        $response->assertStatus(200);
        expect($response->headers->get('Content-Type'))->toContain(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        // Verify products were created in DB
        $this->assertDatabaseCount('products', 3);
    });

    // ─── 200 JSON (Accept: application/json) ─────────────────────────────────

    it('returns 200 JSON with legacy format when Accept: application/json', function () {
        $typeId = createGenerateProductType([
            'primary_model' => 'P-SINGLE',
            'secondary_model' => null,
        ]);

        $response = $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 2]],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['new_products_count', 'product_list']]);

        $data = $response->json('data');
        expect($data['new_products_count'])->toBe(2);
        expect($data['product_list'])->toHaveKey('P-SINGLE');
        expect($data['product_list']['P-SINGLE'])->toHaveCount(2);
    });

    it('records a generation_history row with correct data after successful Excel generation', function () {
        $typeId = createGenerateProductType([
            'primary_model' => 'P-SINGLE',
            'secondary_model' => null,
        ]);

        $this->json('POST', '/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 3]],
        ], [
            'Accept' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->assertOk();

        $this->assertDatabaseCount('generation_history', 1);
        $history = DB::table('generation_history')->first();

        expect($history)->not->toBeNull();
        expect($history->total_count)->toBe(3)
            ->and($history->excel_blob)->not->toBeNull()
            ->and(strlen((string) $history->excel_blob))->toBeGreaterThan(0)
            ->and($history->generated_by_id)->toBe($this->user->id)
            ->and((string) $history->generated_at)->not->toBe('');
    });

    it('records a generation_history row when Accept application json', function () {
        $typeId = createGenerateProductType([
            'primary_model' => 'P-SINGLE',
            'secondary_model' => null,
        ]);

        $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 2]],
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertDatabaseCount('generation_history', 1);
        $this->assertDatabaseHas('generation_history', [
            'total_count' => 2,
            'generated_by_id' => $this->user->id,
        ]);
    });

    it('does not record history when generation fails due to invalid typeId', function () {
        $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => 999999, 'quantity' => 5]],
        ])->assertStatus(422);

        $this->assertDatabaseCount('generation_history', 0);
    });

    it('creates 2500 products across 3 chunks with correct names (chunking coverage)', function () {
        // ~1-3s
        $typeId = createGenerateProductType([
            'primary_model' => 'P-CHUNK',
            'secondary_model' => null,
        ]);

        $response = $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 2500]],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['new_products_count', 'product_list']]);

        $data = $response->json('data');

        expect($data['new_products_count'])->toBe(2500);
        expect($data['product_list'])->toHaveKey('P-CHUNK');
        expect($data['product_list']['P-CHUNK'])->toHaveCount(2500);

        $this->assertDatabaseCount('products', 2500);

        $rows = DB::table('products')
            ->orderBy('id')
            ->get(['id', 'name']);

        expect($rows)->toHaveCount(2500);

        $rows->each(function (object $row): void {
            expect($row->name)->toMatch('/^P-CHUNK \(\d+\)$/');
            expect($row->name)->toBe(sprintf('P-CHUNK (%d)', $row->id));
        });

        $thirdChunkRows = $rows->slice(2000)->values();

        expect($thirdChunkRows)->toHaveCount(500);

        $thirdChunkRows->each(function (object $row): void {
            expect($row->name)->toMatch('/^P-CHUNK \(\d+\)$/');
            expect($row->name)->toBe(sprintf('P-CHUNK (%d)', $row->id));
        });
    });

    // ─── Dual-model generation (FR-003) ───────────────────────────────────────

    it('creates 2 products per unit for dual-model type (FR-003)', function () {
        $typeId = createGenerateProductType([
            'primary_model' => 'P-GG-IG-RC google',
            'secondary_model' => 'P-GG-IG-RC instagram',
        ]);

        $response = $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 3]],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data['new_products_count'])->toBe(6);

        // Verify none are linked
        $this->assertDatabaseMissing('products', ['linked_to_product_id' => DB::table('products')->min('id')]);
    });

    // ─── Product defaults (FR-005) ────────────────────────────────────────────

    it('generates products with active=true, user_id=null, and name pattern "{model} ({id})" (FR-005)', function () {
        $typeId = createGenerateProductType(['primary_model' => 'P-XX']);

        $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 1]],
        ]);

        $product = DB::table('products')->latest('id')->first();

        expect($product)->not->toBeNull();
        expect((bool) $product->active)->toBeTrue();
        expect($product->user_id)->toBeNull();
        expect($product->linked_to_product_id)->toBeNull();
        expect($product->name)->toMatch('/^P-XX \(\d+\)$/');
    });

    // ─── 422 on invalid typeId (FR-001/FR-002) ────────────────────────────────

    it('returns 422 when typeId does not exist (FR-001 / FR-002)', function () {
        $response = $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => 999999, 'quantity' => 5]],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_product_type_ids');

        $this->assertDatabaseCount('products', 0);
    });

    it('returns 422 and creates ZERO products when one typeId in a mixed batch is invalid (all-or-nothing)', function () {
        $validTypeId = createGenerateProductType();

        $response = $this->postJson('/api/v2/products/generate', [
            'items' => [
                ['typeId' => $validTypeId, 'quantity' => 2],
                ['typeId' => 999999, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('products', 0);
    });

    // ─── 400 validation errors (FormRequest) ──────────────────────────────────

    it('returns 400 when items is missing', function () {
        $response = $this->postJson('/api/v2/products/generate', []);

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 400 when quantity is zero', function () {
        $typeId = createGenerateProductType();

        $response = $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 0]],
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error');
    });

    // ─── 401 unauthenticated (NFR-001) ────────────────────────────────────────

    it('returns 401 when not authenticated', function () {
        auth()->guard('stateful-api')->logout();

        $response = $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => 1, 'quantity' => 1]],
        ]);

        $response->assertStatus(401);
    });

    // ─── 403 missing permission (NFR-001) ─────────────────────────────────────

    it('returns 403 when user lacks product_generation permission', function () {
        $userWithoutPermission = $this->create_user(['email' => 'noperm@example.com']);
        $this->actingAs($userWithoutPermission, 'stateful-api');

        $typeId = createGenerateProductType();

        $response = $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 1]],
        ]);

        $response->assertStatus(403);
    });

    // ─── Description fallback (FR-006) ────────────────────────────────────────

    it('uses item description when provided (FR-006)', function () {
        $typeId = createGenerateProductType(['description' => 'Type default']);

        $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 1, 'description' => 'Custom desc']],
        ]);

        $this->assertDatabaseHas('products', ['description' => 'Custom desc']);
    });

    it('falls back to ProductType description when item has none (FR-006)', function () {
        $typeId = createGenerateProductType(['description' => 'Fallback from type']);

        $this->postJson('/api/v2/products/generate', [
            'items' => [['typeId' => $typeId, 'quantity' => 1]],
        ]);

        $this->assertDatabaseHas('products', ['description' => 'Fallback from type']);
    });
});
