<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Infrastructure\Persistence\NullProductUsageAdapter;

// ─── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Insert a product_type row and return its id.
 *
 * @param array<string, mixed> $overrides
 */
function createUsageTestProductType(array $overrides = []): int
{
    $uid = uniqid();
    return DB::table('product_types')->insertGetId(array_merge([
        'code'            => 'TYPE-' . $uid,
        'name'            => 'Type ' . $uid,
        'image_ref'       => 'TYPE-' . $uid,
        'primary_model'   => 'ModelA',
        'secondary_model' => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ], $overrides));
}

/**
 * Insert a product row and return its id.
 *
 * @param array<string, mixed> $overrides
 */
function createUsageTestProduct(array $overrides = []): int
{
    $productTypeId = createUsageTestProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id'     => $productTypeId,
        'user_id'             => null,
        'model'               => 'Model-' . uniqid(),
        'linked_to_product_id' => null,
        'password'            => 'secret',
        'target_url'          => null,
        'usage'               => 0,
        'name'                => 'Test Product',
        'description'         => null,
        'active'              => true,
        'configuration_status' => 'not-started',
        'created_at'          => now(),
        'updated_at'          => now(),
        'deleted_at'          => null,
    ], $overrides));
}

// ─── Setup ─────────────────────────────────────────────────────────────────────

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Seed the configuration_status_codes lookup table required by the products FK constraint.
    $this->seed(ProductConfigurationStatusSeeder::class);

    $this->user = $this->create_user(['email' => 'usage@example.com']);
    $this->actingAs($this->user, 'stateful-api');

    // Rebind ProductUsagePort to NullProductUsageAdapter to avoid real DynamoDB calls.
    // Tests that need to assert on writeUsageEvent replace this with a mock.
    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);
});

// ═══════════════════════════════════════════════════════════════════════════════
// POST /api/v2/products/{id}/usage
// ═══════════════════════════════════════════════════════════════════════════════

describe('POST /api/v2/products/{id}/usage', function () {

    // ─── Success (AC: Usage event is accepted) ─────────────────────────────────

    it('returns 201 when the product exists and the payload is valid', function () {
        $productId = createUsageTestProduct();

        $response = $this->postJson("/api/v2/products/{$productId}/usage", [
            'userId'      => 7,
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15T10:30:00+00:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data']);
    });

    it('calls writeUsageEvent exactly once on success', function () {
        $productId = createUsageTestProduct();

        /** @var \Mockery\MockInterface&ProductUsagePort $usageMock */
        $usageMock = Mockery::mock(ProductUsagePort::class);
        $usageMock->shouldReceive('writeUsageEvent')
            ->once()
            ->withArgs(function (int $pId, int $userId, string $productName, DateTimeImmutable $ts) use ($productId): bool {
                return $pId === $productId
                    && $userId === 7
                    && $productName === 'GPT-4 Pro'
                    && $ts->getTimezone()->getName() === 'UTC';
            });

        $this->app->instance(ProductUsagePort::class, $usageMock);

        $this->postJson("/api/v2/products/{$productId}/usage", [
            'userId'      => 7,
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15T10:30:00+00:00',
        ])->assertStatus(201);
    });

    // ─── Authentication (AC: Request is rejected before write) ────────────────

    it('returns 401 for an unauthenticated request', function () {
        $productId = createUsageTestProduct();

        auth()->guard('stateful-api')->logout();

        $response = $this->postJson("/api/v2/products/{$productId}/usage", [
            'userId'      => 7,
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15T10:30:00+00:00',
        ]);

        $response->assertStatus(401);
    });

    it('does not call writeUsageEvent when unauthenticated', function () {
        $productId = createUsageTestProduct();

        /** @var \Mockery\MockInterface&ProductUsagePort $usageMock */
        $usageMock = Mockery::mock(ProductUsagePort::class);
        $usageMock->shouldNotReceive('writeUsageEvent');

        $this->app->instance(ProductUsagePort::class, $usageMock);

        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$productId}/usage", [
            'userId'      => 7,
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15T10:30:00+00:00',
        ])->assertStatus(401);
    });

    // ─── 404 (product not found) ───────────────────────────────────────────────

    it('returns 404 when the product does not exist', function () {
        $response = $this->postJson('/api/v2/products/999999/usage', [
            'userId'      => 7,
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15T10:30:00+00:00',
        ]);

        $response->assertStatus(404);
    });

    it('does not call writeUsageEvent when product is not found', function () {
        /** @var \Mockery\MockInterface&ProductUsagePort $usageMock */
        $usageMock = Mockery::mock(ProductUsagePort::class);
        $usageMock->shouldNotReceive('writeUsageEvent');

        $this->app->instance(ProductUsagePort::class, $usageMock);

        $this->postJson('/api/v2/products/999999/usage', [
            'userId'      => 7,
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15T10:30:00+00:00',
        ])->assertStatus(404);
    });

    // ─── 422 Validation errors (AC: Request is rejected before write) ────────

    it('returns 422 when userId is missing', function () {
        $productId = createUsageTestProduct();

        $response = $this->postJson("/api/v2/products/{$productId}/usage", [
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15T10:30:00+00:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 422 when productName is missing', function () {
        $productId = createUsageTestProduct();

        $response = $this->postJson("/api/v2/products/{$productId}/usage", [
            'userId'    => 7,
            'scannedAt' => '2024-06-15T10:30:00+00:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 422 when scannedAt is missing', function () {
        $productId = createUsageTestProduct();

        $response = $this->postJson("/api/v2/products/{$productId}/usage", [
            'userId'      => 7,
            'productName' => 'GPT-4 Pro',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 422 when scannedAt is not a valid ISO-8601 datetime', function () {
        $productId = createUsageTestProduct();

        $response = $this->postJson("/api/v2/products/{$productId}/usage", [
            'userId'      => 7,
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => 'not-a-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 422 when scannedAt is a valid date but not ISO-8601 (no T separator or no offset)', function () {
        $productId = createUsageTestProduct();

        $response = $this->postJson("/api/v2/products/{$productId}/usage", [
            'userId'      => 7,
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15 10:30:00',  // valid date, but not ISO-8601 with offset
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 422 when userId is not an integer', function () {
        $productId = createUsageTestProduct();

        $response = $this->postJson("/api/v2/products/{$productId}/usage", [
            'userId'      => 'not-an-int',
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15T10:30:00+00:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('accepts ISO-8601 datetime with UTC Z shorthand', function () {
        $productId = createUsageTestProduct();

        $response = $this->postJson("/api/v2/products/{$productId}/usage", [
            'userId'      => 7,
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15T10:30:00Z',
        ]);

        $response->assertStatus(201);
    });

    it('does not call writeUsageEvent on validation error', function () {
        $productId = createUsageTestProduct();

        /** @var \Mockery\MockInterface&ProductUsagePort $usageMock */
        $usageMock = Mockery::mock(ProductUsagePort::class);
        $usageMock->shouldNotReceive('writeUsageEvent');

        $this->app->instance(ProductUsagePort::class, $usageMock);

        // Missing userId — should be caught by FormRequest before handler is called
        $this->postJson("/api/v2/products/{$productId}/usage", [
            'productName' => 'GPT-4 Pro',
            'scannedAt'   => '2024-06-15T10:30:00+00:00',
        ])->assertStatus(422);
    });
});
