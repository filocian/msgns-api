<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductBusiness;
use App\Models\ProductType;
use App\Static\Permissions\StaticRoles;
use Carbon\CarbonImmutable;
use Database\Seeders\ProductConfigurationStatusSeeder;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

/**
 * @param array<string, mixed> $overrides
 */
function createListProductType(array $overrides = []): ProductType
{
    /** @var ProductType $productType */
    $productType = ProductType::factory()->create(array_merge([
        'code' => 'nfc-card-' . str()->lower(str()->random(8)),
        'name' => 'NFC Card ' . str()->lower(str()->random(4)),
        'primary_model' => 'nfc',
    ], $overrides));

    return $productType;
}

/**
 * @param array<string, mixed> $overrides
 */
function createListProduct(array $overrides = []): Product
{
    $productType = $overrides['product_type_id'] ?? createListProductType()->id;

    /** @var Product $product */
    $product = Product::factory()->create(array_merge([
        'product_type_id' => $productType,
        'model' => 'nfc',
        'linked_to_product_id' => null,
        'target_url' => 'https://example.com/default',
        'active' => true,
        'configuration_status' => ConfigurationStatus::ASSIGNED,
        'assigned_at' => CarbonImmutable::now(),
        'usage' => 0,
        'name' => 'Product',
    ], $overrides));

    return $product;
}

function attachBusiness(Product $product, array $types = ['restaurant' => true], ?string $size = 'small'): ProductBusiness
{
    /** @var ProductBusiness $business */
    $business = ProductBusiness::factory()->create([
        'product_id' => $product->id,
        'user_id' => $product->user_id,
        'types' => $types,
        'size' => $size,
    ]);

    return $business;
}

beforeEach(function (): void {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(ProductConfigurationStatusSeeder::class);
    $this->user = $this->create_user(['email' => 'list-products@example.com']);
    $this->actingAs($this->user, 'stateful-api');
});

describe('GET /api/v2/products/', function (): void {
    it('returns 401 when unauthenticated', function (): void {
        auth()->guard('stateful-api')->logout();

        $this->getJson('/api/v2/products/')->assertStatus(401);
    });

    it('returns paginated list with defaults', function (): void {
        $baseTime = CarbonImmutable::create(2025, 1, 1, 12, 0, 0, 'UTC');

        foreach (range(1, 20) as $index) {
            createListProduct([
                'user_id' => $this->user->id,
                'name' => 'Product ' . $index,
                'assigned_at' => $baseTime->subMinutes($index),
            ]);
        }

        $response = $this->getJson('/api/v2/products/')->assertOk();

        expect($response->json('data'))->toHaveCount(15)
            ->and($response->json('meta.current_page'))->toBe(1)
            ->and($response->json('meta.per_page'))->toBe(15)
            ->and($response->json('meta.total'))->toBe(20)
            ->and($response->json('meta.last_page'))->toBe(2)
            ->and($response->json('data.0.assigned_at'))
            ->toBe($baseTime->subMinutes(1)->toIso8601String());
    });

    it('returns second page', function (): void {
        foreach (range(1, 20) as $index) {
            createListProduct([
                'user_id' => $this->user->id,
                'assigned_at' => CarbonImmutable::now()->subMinutes($index),
            ]);
        }

        $response = $this->getJson('/api/v2/products/?page=2')->assertOk();

        expect($response->json('data'))->toHaveCount(5)
            ->and($response->json('meta.current_page'))->toBe(2);
    });

    it('respects custom per_page', function (): void {
        foreach (range(1, 10) as $index) {
            createListProduct(['user_id' => $this->user->id, 'name' => 'P' . $index]);
        }

        $response = $this->getJson('/api/v2/products/?per_page=5')->assertOk();

        expect($response->json('data'))->toHaveCount(5)
            ->and($response->json('meta.per_page'))->toBe(5);
    });

    it('clamps per_page to 100', function (): void {
        foreach (range(1, 101) as $index) {
            createListProduct(['user_id' => $this->user->id, 'name' => 'P' . $index]);
        }

        $response = $this->getJson('/api/v2/products/?per_page=200')->assertOk();

        expect($response->json('meta.per_page'))->toBe(100)
            ->and($response->json('data'))->toHaveCount(100);
    });

    it('returns only products belonging to authenticated user', function (): void {
        $otherUser = $this->create_user(['email' => 'list-products-other@example.com']);

        createListProduct(['user_id' => $this->user->id, 'name' => 'Mine 1', 'assigned_at' => CarbonImmutable::create(2025, 1, 1, 9, 0, 0, 'UTC')]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Mine 2', 'assigned_at' => CarbonImmutable::create(2025, 1, 1, 10, 0, 0, 'UTC')]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Mine 3', 'assigned_at' => CarbonImmutable::create(2025, 1, 1, 11, 0, 0, 'UTC')]);
        createListProduct(['user_id' => $otherUser->id, 'name' => 'Other 1']);
        createListProduct(['user_id' => $otherUser->id, 'name' => 'Other 2']);

        $response = $this->getJson('/api/v2/products/')->assertOk();

        expect($response->json('meta.total'))->toBe(3)
            ->and(collect($response->json('data'))->pluck('name')->all())
            ->toBe(['Mine 3', 'Mine 2', 'Mine 1']);
    });

    it('excludes linked secondary products', function (): void {
        $primary = createListProduct(['user_id' => $this->user->id, 'name' => 'Primary']);
        createListProduct([
            'user_id' => $this->user->id,
            'name' => 'Secondary',
            'linked_to_product_id' => $primary->id,
        ]);

        $response = $this->getJson('/api/v2/products/')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Primary');
    });

    it('excludes soft-deleted products', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Visible']);
        $deleted = createListProduct(['user_id' => $this->user->id, 'name' => 'Deleted']);
        $deleted->delete();

        $response = $this->getJson('/api/v2/products/')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Visible');
    });

    it('filters by configuration_status=completed', function (): void {
        createListProduct(['user_id' => $this->user->id, 'configuration_status' => ConfigurationStatus::COMPLETED]);
        createListProduct(['user_id' => $this->user->id, 'configuration_status' => ConfigurationStatus::ASSIGNED]);

        $response = $this->getJson('/api/v2/products/?configuration_status=completed')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.configuration_status'))->toBe(ConfigurationStatus::COMPLETED);
    });

    it('unrecognised configuration_status returns empty set', function (): void {
        createListProduct(['user_id' => $this->user->id, 'configuration_status' => ConfigurationStatus::ASSIGNED]);

        $response = $this->getJson('/api/v2/products/?configuration_status=invalid')->assertOk();

        expect($response->json('data'))->toBe([])
            ->and($response->json('meta.total'))->toBe(0);
    });

    it('filters by active=true', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Active', 'active' => true]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Inactive', 'active' => false]);

        $response = $this->getJson('/api/v2/products/?active=true')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Active');
    });

    it('filters by active=false', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Active', 'active' => true]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Inactive', 'active' => false]);

        $response = $this->getJson('/api/v2/products/?active=false')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Inactive');
    });

    it('omitting active returns both active and inactive', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Active', 'active' => true]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Inactive', 'active' => false]);

        $response = $this->getJson('/api/v2/products/')->assertOk();

        expect($response->json('meta.total'))->toBe(2);
    });

    it('filters by model exact match', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'NFC', 'model' => 'nfc']);
        createListProduct(['user_id' => $this->user->id, 'name' => 'WhatsApp', 'model' => 'whatsapp']);

        $response = $this->getJson('/api/v2/products/?model=nfc')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.model'))->toBe('nfc');
    });

    it('model filter with no matching products returns empty set', function (): void {
        createListProduct(['user_id' => $this->user->id, 'model' => 'nfc']);

        $response = $this->getJson('/api/v2/products/?model=whatsapp')->assertOk();

        expect($response->json('data'))->toBe([])
            ->and($response->json('meta.total'))->toBe(0);
    });

    it('filters by target_url partial match', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Match', 'target_url' => 'https://myshop.example.com/page']);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Other', 'target_url' => 'https://other.io']);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Null', 'target_url' => null]);

        $response = $this->getJson('/api/v2/products/?target_url=example.com')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Match');
    });

    it('null target_url products excluded when filter applied', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Null', 'target_url' => null]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Match', 'target_url' => 'https://example.com/value']);

        $response = $this->getJson('/api/v2/products/?target_url=value')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Match');
    });

    it('target_url escapes SQL percent wildcard', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Percent', 'target_url' => 'https://shop.example.com/100%off']);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Other', 'target_url' => 'https://shop.example.com/1000off']);

        $response = $this->getJson('/api/v2/products/?target_url=100%25off')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Percent');
    });

    it('target_url escapes SQL underscore wildcard', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Underscore', 'target_url' => 'https://example.com/user_profile']);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Wildcard', 'target_url' => 'https://example.com/userXprofile']);

        $response = $this->getJson('/api/v2/products/?target_url=user_profile')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Underscore');
    });

    it('filters by has_business_info=true', function (): void {
        $withBusiness = createListProduct(['user_id' => $this->user->id, 'name' => 'With Business']);
        $withoutBusiness = createListProduct(['user_id' => $this->user->id, 'name' => 'Without Business']);
        attachBusiness($withBusiness);

        $response = $this->getJson('/api/v2/products/?has_business_info=true')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('With Business')
            ->and($response->json('data.0.business.types.restaurant'))->toBeTrue();
    });

    it('filters by has_business_info=false', function (): void {
        $withBusiness = createListProduct(['user_id' => $this->user->id, 'name' => 'With Business']);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Without Business']);
        attachBusiness($withBusiness);

        $response = $this->getJson('/api/v2/products/?has_business_info=false')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Without Business')
            ->and($response->json('data.0.business'))->toBeNull();
    });

    it('applies multiple filters simultaneously', function (): void {
        createListProduct([
            'user_id' => $this->user->id,
            'name' => 'Match',
            'active' => true,
            'configuration_status' => ConfigurationStatus::COMPLETED,
            'model' => 'nfc',
        ]);
        createListProduct([
            'user_id' => $this->user->id,
            'name' => 'No Match',
            'active' => true,
            'configuration_status' => ConfigurationStatus::ASSIGNED,
            'model' => 'nfc',
        ]);

        $response = $this->getJson('/api/v2/products/?active=true&configuration_status=completed&model=nfc')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Match');
    });

    it('sorts by name asc', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Zebra']);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Apple']);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Mango']);

        $response = $this->getJson('/api/v2/products/?sort_by=name&sort_dir=asc')->assertOk();

        expect(collect($response->json('data'))->pluck('name')->take(3)->values()->all())
            ->toBe(['Apple', 'Mango', 'Zebra']);
    });

    it('sorts by usage desc', function (): void {
        createListProduct(['user_id' => $this->user->id, 'usage' => 10]);
        createListProduct(['user_id' => $this->user->id, 'usage' => 50]);
        createListProduct(['user_id' => $this->user->id, 'usage' => 5]);

        $response = $this->getJson('/api/v2/products/?sort_by=usage&sort_dir=desc')->assertOk();

        expect(collect($response->json('data'))->pluck('usage')->take(3)->values()->all())
            ->toBe([50, 10, 5]);
    });

    it('sorts by configuration_status asc and puts completed products first', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Assigned', 'configuration_status' => ConfigurationStatus::ASSIGNED, 'assigned_at' => CarbonImmutable::now()->subDay()]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Completed New', 'configuration_status' => ConfigurationStatus::COMPLETED, 'assigned_at' => CarbonImmutable::now()]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Not Started', 'configuration_status' => ConfigurationStatus::NOT_STARTED, 'assigned_at' => CarbonImmutable::now()->subHours(12)]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Completed Old', 'configuration_status' => ConfigurationStatus::COMPLETED, 'assigned_at' => CarbonImmutable::now()->subHour()]);

        $response = $this->getJson('/api/v2/products/?sort_by=configuration_status&sort_dir=asc')->assertOk();

        expect(collect($response->json('data'))->pluck('name')->take(4)->values()->all())
            ->toBe(['Completed New', 'Completed Old', 'Not Started', 'Assigned']);
    });

    it('sorts by configuration_status desc and puts non-completed products first', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Assigned', 'configuration_status' => ConfigurationStatus::ASSIGNED, 'assigned_at' => CarbonImmutable::now()->subDay()]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Completed', 'configuration_status' => ConfigurationStatus::COMPLETED, 'assigned_at' => CarbonImmutable::now()]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Business', 'configuration_status' => ConfigurationStatus::BUSINESS_SET, 'assigned_at' => CarbonImmutable::now()->subHour()]);

        $response = $this->getJson('/api/v2/products/?sort_by=configuration_status&sort_dir=desc')->assertOk();

        expect(collect($response->json('data'))->pluck('name')->take(3)->values()->all())
            ->toBe(['Business', 'Assigned', 'Completed']);
    });

    it('invalid sort_by falls back to assigned_at desc', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Oldest', 'assigned_at' => CarbonImmutable::now()->subDays(2)]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Newest', 'assigned_at' => CarbonImmutable::now()]);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Middle', 'assigned_at' => CarbonImmutable::now()->subDay()]);

        $response = $this->getJson('/api/v2/products/?sort_by=foobar')->assertOk();

        expect(collect($response->json('data'))->pluck('name')->take(3)->values()->all())
            ->toBe(['Newest', 'Middle', 'Oldest']);
    });

    it('invalid sort_dir falls back to desc', function (): void {
        createListProduct(['user_id' => $this->user->id, 'name' => 'Apple']);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Mango']);
        createListProduct(['user_id' => $this->user->id, 'name' => 'Zebra']);

        $response = $this->getJson('/api/v2/products/?sort_by=name&sort_dir=sideways')->assertOk();

        expect(collect($response->json('data'))->pluck('name')->take(3)->values()->all())
            ->toBe(['Zebra', 'Mango', 'Apple']);
    });

    it('includes product_type object with id code and name', function (): void {
        $type = createListProductType(['code' => 'nfc-card', 'name' => 'NFC Card']);
        createListProduct(['user_id' => $this->user->id, 'product_type_id' => $type->id]);

        $response = $this->getJson('/api/v2/products/')->assertOk();

        expect($response->json('data.0.product_type'))->toBe([
            'id' => $type->id,
            'code' => 'nfc-card',
            'name' => 'NFC Card',
        ]);
    });

    it('includes business object when product_business record exists', function (): void {
        $product = createListProduct(['user_id' => $this->user->id]);
        attachBusiness($product, ['restaurant' => true], 'small');

        $response = $this->getJson('/api/v2/products/')->assertOk();

        expect($response->json('data.0.business'))->toBe([
            'types' => ['restaurant' => true],
            'size' => 'small',
        ]);
    });

    it('business is null when no product_business record', function (): void {
        createListProduct(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v2/products/')->assertOk();

        expect($response->json('data.0.business'))->toBeNull();
    });

    it('assigned_at is ISO 8601 format', function (): void {
        $assignedAt = CarbonImmutable::create(2025, 10, 15, 14, 30, 0, 'UTC');
        createListProduct(['user_id' => $this->user->id, 'assigned_at' => $assignedAt]);

        $response = $this->getJson('/api/v2/products/')->assertOk();

        expect($response->json('data.0.assigned_at'))->toBe($assignedAt->toIso8601String());
    });

    it('returns empty list when user has no products', function (): void {
        $response = $this->getJson('/api/v2/products/')->assertOk();

        expect($response->json('data'))->toBe([])
            ->and($response->json('meta.total'))->toBe(0)
            ->and($response->json('meta.last_page'))->toBe(1);
    });

    it('page beyond last_page returns empty data', function (): void {
        foreach (range(1, 3) as $index) {
            createListProduct(['user_id' => $this->user->id, 'name' => 'Product ' . $index]);
        }

        $response = $this->getJson('/api/v2/products/?page=99')->assertOk();

        expect($response->json('data'))->toBe([])
            ->and($response->json('meta.total'))->toBe(3);
    });

    it('includes overview with correct counters on default request (S-25)', function (): void {
        $productType = createListProductType();

        foreach (range(1, 4) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::COMPLETED,
                'active' => true,
            ]);
        }

        foreach (range(1, 3) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::ASSIGNED,
                'active' => false,
            ]);
        }

        foreach (range(1, 3) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::ASSIGNED,
                'active' => true,
            ]);
        }

        $response = $this->getJson('/api/v2/products/');

        $response->assertOk()
            ->assertJsonPath('overview.total_products', 10)
            ->assertJsonPath('overview.pending_configuration', 6)
            ->assertJsonPath('overview.paused', 3);
    });

    it('overview ignores configuration_status filter (S-26)', function (): void {
        $productType = createListProductType();

        foreach (range(1, 4) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::COMPLETED,
                'active' => true,
            ]);
        }

        foreach (range(1, 6) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::ASSIGNED,
                'active' => true,
            ]);
        }

        foreach (range(1, 3) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::ASSIGNED,
                'active' => false,
            ]);
        }

        $response = $this->getJson('/api/v2/products/?configuration_status=completed');

        $response->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('overview.total_products', 13)
            ->assertJsonPath('overview.pending_configuration', 9)
            ->assertJsonPath('overview.paused', 3);
    });

    it('overview ignores active filter (S-27)', function (): void {
        $productType = createListProductType();

        foreach (range(1, 7) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'active' => true,
                'configuration_status' => ConfigurationStatus::COMPLETED,
            ]);
        }

        foreach (range(1, 3) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'active' => false,
                'configuration_status' => ConfigurationStatus::ASSIGNED,
            ]);
        }

        $response = $this->getJson('/api/v2/products/?active=false');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('overview.total_products', 10)
            ->assertJsonPath('overview.paused', 3)
            ->assertJsonPath('overview.pending_configuration', 3);
    });

    it('overview ignores pagination (S-28)', function (): void {
        $productType = createListProductType();

        foreach (range(1, 12) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::COMPLETED,
                'active' => true,
            ]);
        }

        foreach (range(1, 2) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::ASSIGNED,
                'active' => true,
            ]);
        }

        foreach (range(1, 3) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::ASSIGNED,
                'active' => false,
            ]);
        }

        $response = $this->getJson('/api/v2/products/?page=2&per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 17)
            ->assertJsonPath('overview.total_products', 17)
            ->assertJsonPath('overview.pending_configuration', 5)
            ->assertJsonPath('overview.paused', 3);
    });

    it('overview is zero when user has no products (S-29)', function (): void {
        $response = $this->getJson('/api/v2/products/');

        $response->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('overview.total_products', 0)
            ->assertJsonPath('overview.pending_configuration', 0)
            ->assertJsonPath('overview.paused', 0);
    });

    it('overview excludes soft-deleted products (S-30)', function (): void {
        $productType = createListProductType();

        createListProduct([
            'user_id' => $this->user->id,
            'product_type_id' => $productType->id,
            'configuration_status' => ConfigurationStatus::ASSIGNED,
            'active' => false,
        ]);

        $deleted = createListProduct([
            'user_id' => $this->user->id,
            'product_type_id' => $productType->id,
            'configuration_status' => ConfigurationStatus::NOT_STARTED,
            'active' => false,
        ]);
        $deleted->delete();

        $response = $this->getJson('/api/v2/products/');

        $response->assertOk()
            ->assertJsonPath('overview.total_products', 1)
            ->assertJsonPath('overview.pending_configuration', 1)
            ->assertJsonPath('overview.paused', 1);
    });

    it('overview excludes secondary products (S-31)', function (): void {
        $productType = createListProductType();

        $primary = createListProduct([
            'user_id' => $this->user->id,
            'product_type_id' => $productType->id,
            'configuration_status' => ConfigurationStatus::ASSIGNED,
            'active' => false,
        ]);

        createListProduct([
            'user_id' => $this->user->id,
            'product_type_id' => $productType->id,
            'linked_to_product_id' => $primary->id,
            'configuration_status' => ConfigurationStatus::NOT_STARTED,
            'active' => false,
        ]);

        $response = $this->getJson('/api/v2/products/');

        $response->assertOk()
            ->assertJsonPath('overview.total_products', 1)
            ->assertJsonPath('overview.pending_configuration', 1)
            ->assertJsonPath('overview.paused', 1);
    });

    it('counts product in both pending_configuration and paused when both conditions apply (S-32)', function (): void {
        $productType = createListProductType();

        createListProduct([
            'user_id' => $this->user->id,
            'product_type_id' => $productType->id,
            'configuration_status' => ConfigurationStatus::NOT_STARTED,
            'active' => false,
        ]);
        createListProduct([
            'user_id' => $this->user->id,
            'product_type_id' => $productType->id,
            'configuration_status' => ConfigurationStatus::COMPLETED,
            'active' => false,
        ]);
        createListProduct([
            'user_id' => $this->user->id,
            'product_type_id' => $productType->id,
            'configuration_status' => ConfigurationStatus::ASSIGNED,
            'active' => true,
        ]);

        $response = $this->getJson('/api/v2/products/');

        $response->assertOk()
            ->assertJsonPath('overview.total_products', 3)
            ->assertJsonPath('overview.pending_configuration', 2)
            ->assertJsonPath('overview.paused', 2);
    });

    it('overview only counts products belonging to the authenticated user (S-33)', function (): void {
        $otherUser = $this->create_user(['email' => 'overview-other@example.com']);
        $productType = createListProductType();

        foreach (range(1, 3) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::COMPLETED,
                'active' => true,
            ]);
        }

        foreach (range(1, 2) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::ASSIGNED,
                'active' => true,
            ]);
        }

        createListProduct([
            'user_id' => $this->user->id,
            'product_type_id' => $productType->id,
            'configuration_status' => ConfigurationStatus::COMPLETED,
            'active' => false,
        ]);

        foreach (range(1, 8) as $_) {
            createListProduct([
                'user_id' => $otherUser->id,
                'product_type_id' => $productType->id,
                'configuration_status' => ConfigurationStatus::ASSIGNED,
                'active' => false,
            ]);
        }

        $response = $this->getJson('/api/v2/products/');

        $response->assertOk()
            ->assertJsonPath('overview.total_products', 6)
            ->assertJsonPath('overview.pending_configuration', 2)
            ->assertJsonPath('overview.paused', 1);
    });

    it('other endpoints using ApiResponseFactory paginated do not include overview (S-34)', function (): void {
        $admin = $this->create_user(['email' => 'admin-overview@example.com']);
        $admin->assignRole($this->createRole(StaticRoles::DEV_ROLE));

        $response = $this->actingAs($admin, 'stateful-api')->getJson('/api/v2/products/admin/');

        $response->assertOk()
            ->assertJsonMissingPath('overview');
    });

    it('overview ignores all combined filters (S-35)', function (): void {
        $productTypeNfc = createListProductType(['primary_model' => 'nfc']);
        $productTypeWhatsapp = createListProductType(['primary_model' => 'whatsapp']);

        foreach (range(1, 8) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productTypeNfc->id,
                'model' => 'nfc',
                'active' => true,
                'configuration_status' => ConfigurationStatus::ASSIGNED,
            ]);
        }

        foreach (range(1, 12) as $_) {
            createListProduct([
                'user_id' => $this->user->id,
                'product_type_id' => $productTypeWhatsapp->id,
                'model' => 'whatsapp',
                'active' => false,
                'configuration_status' => ConfigurationStatus::COMPLETED,
            ]);
        }

        $response = $this->getJson('/api/v2/products/?model=nfc&active=true&configuration_status=assigned');

        $response->assertOk()
            ->assertJsonCount(8, 'data')
            ->assertJsonPath('overview.total_products', 20)
            ->assertJsonPath('overview.pending_configuration', 8)
            ->assertJsonPath('overview.paused', 12);
    });
});
