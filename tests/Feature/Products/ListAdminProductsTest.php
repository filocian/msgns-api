<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductBusiness;
use App\Models\ProductType;
use App\Models\User;
use App\Static\Permissions\StaticRoles;
use Carbon\CarbonImmutable;
use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Testing\TestResponse;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** @mixin TestCase */

final class AdminListTestState
{
    public function __construct(
        public User $admin,
        public RoleContract|Role $developerRole,
        public RoleContract|Role $backofficeRole,
    ) {}
}

function adminListState(?AdminListTestState $state = null): AdminListTestState
{
    /** @var AdminListTestState|null $current */
    static $current = null;

    if ($state instanceof AdminListTestState) {
        $current = $state;
    }

    assert($current instanceof AdminListTestState);

    return $current;
}

/**
 * @param TestResponse<Response> $response
 * @return list<array<string, mixed>>
 */
function responseItems(TestResponse $response): array
{
    $items = $response->json('data');

    return is_array($items) ? array_values($items) : [];
}

/**
 * @param TestResponse<Response> $response
 * @return list<string>
 */
function responseNames(TestResponse $response): array
{
    return array_map(
        static fn (array $item): string => is_string($item['name'] ?? null) ? $item['name'] : '',
        responseItems($response),
    );
}

/**
 * @param TestResponse<Response> $response
 * @return list<int>
 */
function responseUsages(TestResponse $response): array
{
    return array_map(
        static fn (array $item): int => is_int($item['usage'] ?? null) ? $item['usage'] : 0,
        responseItems($response),
    );
}

/**
 * @param TestResponse<Response> $response
 * @return list<bool>
 */
function responseActives(TestResponse $response): array
{
    return array_map(
        static fn (array $item): bool => is_bool($item['active'] ?? null) ? $item['active'] : false,
        responseItems($response),
    );
}

/**
 * @param TestResponse<Response> $response
 * @return list<string>
 */
function responseModels(TestResponse $response): array
{
    return array_map(
        static fn (array $item): string => is_string($item['model'] ?? null) ? $item['model'] : '',
        responseItems($response),
    );
}

/**
 * @param TestResponse<Response> $response
 * @return list<string>
 */
function responseUserEmails(TestResponse $response): array
{
    return array_values(array_filter(array_map(
        static function (array $item): ?string {
            $user = $item['user'] ?? null;

            return is_array($user) && is_string($user['email'] ?? null) ? $user['email'] : null;
        },
        responseItems($response),
    )));
}

/**
 * @param array<string, mixed> $overrides
 */
function createAdminListProductType(array $overrides = []): ProductType
{
    /** @var ProductType $productType */
    $productType = ProductType::factory()->create(array_merge([
        'code' => 'admin-type-' . str()->lower(str()->random(8)),
        'name' => 'Admin Type ' . str()->lower(str()->random(4)),
        'primary_model' => 'nfc',
    ], $overrides));

    return $productType;
}

/**
 * @param array<string, mixed> $overrides
 */
function createAdminListProduct(array $overrides = []): Product
{
    $productTypeId = $overrides['product_type_id'] ?? createAdminListProductType()->id;

    /** @var Product $product */
    $product = Product::factory()->create(array_merge([
        'product_type_id' => $productTypeId,
        'model' => 'nfc',
        'linked_to_product_id' => null,
        'target_url' => 'https://example.com/default',
        'active' => true,
        'configuration_status' => ConfigurationStatus::ASSIGNED,
        'assigned_at' => CarbonImmutable::now(),
        'usage' => 0,
        'name' => 'Admin Product',
    ], $overrides));

    return $product;
}

/**
 * @param array<string, bool> $types
 */
function attachAdminBusiness(Product $product, ?int $userId = null, array $types = ['restaurant' => true], ?string $size = 'small'): ProductBusiness
{
    /** @var ProductBusiness $business */
    $business = ProductBusiness::factory()->create([
        'product_id' => $product->id,
        'user_id' => $userId,
        'types' => $types,
        'size' => $size,
    ]);

    return $business;
}

beforeEach(function (): void {
    /** @var TestCase $test */
    $test = $this;
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $test->seed(ProductConfigurationStatusSeeder::class);

    $admin = $test->create_user(['email' => 'admin-products@example.com']);
    $developerRole = $test->createRole(StaticRoles::DEV_ROLE);
    $backofficeRole = $test->createRole(StaticRoles::BACKOFFICE_ROLE);

    $admin->assignRole($developerRole);
    adminListState(new AdminListTestState($admin, $developerRole, $backofficeRole));

    $test->actingAs($admin, 'stateful-api');
});

describe('GET /api/v2/products/admin/', function (): void {
    it('returns 401 when unauthenticated', function (): void {
        /** @var TestCase $test */
        $test = $this;
        auth()->guard('stateful-api')->logout();

        $test->getJson('/api/v2/products/admin/')->assertStatus(401);
    });

    it('returns 403 for authenticated users without admin roles', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $user = $test->create_user(['email' => 'regular-admin-products@example.com']);
        $test->actingAs($user, 'stateful-api');

        $test->getJson('/api/v2/products/admin/')->assertStatus(403);
    });

    it('returns paginated results for developer role without user scope', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $state = adminListState();
        $otherUser = $test->create_user(['email' => 'other-owner@example.com']);
        $baseTime = CarbonImmutable::parse('2025-01-01 12:00:00 UTC');

        foreach (range(1, 20) as $index) {
            createAdminListProduct([
                'user_id' => $index <= 10 ? $state->admin->id : $otherUser->id,
                'name' => 'Admin Product ' . $index,
                'assigned_at' => $baseTime->subMinutes($index),
            ]);
        }

        $response = $test->getJson('/api/v2/products/admin/')->assertOk();

        expect($response->json('data'))->toHaveCount(15)
            ->and($response->json('meta.current_page'))->toBe(1)
            ->and($response->json('meta.per_page'))->toBe(15)
            ->and($response->json('meta.total'))->toBe(20)
            ->and($response->json('meta.last_page'))->toBe(2)
            ->and(array_values(array_unique(responseUserEmails($response))))
            ->toContain('admin-products@example.com', 'other-owner@example.com');
    });

    it('also allows backoffice role', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $state = adminListState();
        $backoffice = $test->create_user(['email' => 'backoffice-products@example.com']);
        $backoffice->assignRole($state->backofficeRole);
        $test->actingAs($backoffice, 'stateful-api');
        createAdminListProduct(['user_id' => $state->admin->id, 'name' => 'Visible']);

        $test->getJson('/api/v2/products/admin/')->assertOk()
            ->assertJsonPath('meta.total', 1);
    });

    it('filters by product_type_code exact match', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $nfc = createAdminListProductType(['code' => 'nfc-card']);
        $qr = createAdminListProductType(['code' => 'qr-card']);

        createAdminListProduct(['product_type_id' => $nfc->id, 'name' => 'NFC']);
        createAdminListProduct(['product_type_id' => $qr->id, 'name' => 'QR']);

        $response = $test->getJson('/api/v2/products/admin/?product_type_code=nfc-card')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.product_type.code'))->toBe('nfc-card');
    });

    it('filters by product_type_id exact match', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $type = createAdminListProductType();
        createAdminListProduct(['product_type_id' => $type->id, 'name' => 'Match']);
        createAdminListProduct(['name' => 'Other']);

        $response = $test->getJson('/api/v2/products/admin/?product_type_id=' . $type->id)->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Match');
    });

    it('filters by model exact match', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'NFC', 'model' => 'nfc']);
        createAdminListProduct(['name' => 'WhatsApp', 'model' => 'whatsapp']);

        $response = $test->getJson('/api/v2/products/admin/?model=whatsapp')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.model'))->toBe('whatsapp');
    });

    it('filters by name partial match and escapes wildcards', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => '100% Real Product']);
        createAdminListProduct(['name' => '1000 Real Product']);

        $response = $test->getJson('/api/v2/products/admin/?name=100%25 Real')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('100% Real Product');
    });

    it('filters by user_id exact match', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $state = adminListState();
        $otherUser = $test->create_user(['email' => 'owner-id@example.com']);
        createAdminListProduct(['user_id' => $state->admin->id, 'name' => 'Mine']);
        createAdminListProduct(['user_id' => $otherUser->id, 'name' => 'Theirs']);

        $response = $test->getJson('/api/v2/products/admin/?user_id=' . $otherUser->id)->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.user.email'))->toBe('owner-id@example.com');
    });

    it('filters by user_email partial match and escapes underscore wildcard', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $exact = $test->create_user(['email' => 'john_doe@example.com']);
        $wildcard = $test->create_user(['email' => 'johnXdoe@example.com']);
        createAdminListProduct(['user_id' => $exact->id, 'name' => 'Exact']);
        createAdminListProduct(['user_id' => $wildcard->id, 'name' => 'Wildcard']);

        $response = $test->getJson('/api/v2/products/admin/?user_email=john_doe')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.user.email'))->toBe('john_doe@example.com');
    });

    it('filters by assigned_at date range', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'March 10', 'assigned_at' => CarbonImmutable::create(2025, 3, 10, 10, 0, 0, 'UTC')]);
        createAdminListProduct(['name' => 'March 15', 'assigned_at' => CarbonImmutable::create(2025, 3, 15, 10, 0, 0, 'UTC')]);
        createAdminListProduct(['name' => 'March 20', 'assigned_at' => CarbonImmutable::create(2025, 3, 20, 10, 0, 0, 'UTC')]);

        $response = $test->getJson('/api/v2/products/admin/?assigned_at_from=2025-03-11&assigned_at_to=2025-03-19')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('March 15');
    });

    it('filters by configuration_status exact match', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Completed', 'configuration_status' => ConfigurationStatus::COMPLETED]);
        createAdminListProduct(['name' => 'Assigned', 'configuration_status' => ConfigurationStatus::ASSIGNED]);

        $response = $test->getJson('/api/v2/products/admin/?configuration_status=completed')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.configuration_status'))->toBe(ConfigurationStatus::COMPLETED);
    });

    it('filters by active exact match', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Active', 'active' => true]);
        createAdminListProduct(['name' => 'Inactive', 'active' => false]);

        $response = $test->getJson('/api/v2/products/admin/?active=false')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Inactive');
    });

    it('filters by target_url partial match and escapes wildcards', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Exact', 'target_url' => 'https://shop.example.com/100%off']);
        createAdminListProduct(['name' => 'Wildcard', 'target_url' => 'https://shop.example.com/1000off']);

        $response = $test->getJson('/api/v2/products/admin/?target_url=100%25off')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Exact');
    });

    it('filters by business_type JSON key existence', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $restaurant = createAdminListProduct(['name' => 'Restaurant']);
        $agency = createAdminListProduct(['name' => 'Agency']);
        attachAdminBusiness($restaurant, $restaurant->getAttribute('user_id'), ['restaurant' => true]);
        attachAdminBusiness($agency, $agency->getAttribute('user_id'), ['agency' => true]);

        $response = $test->getJson('/api/v2/products/admin/?business_type=restaurant')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Restaurant');
    });

    it('filters by business_size exact match', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $small = createAdminListProduct(['name' => 'Small']);
        $large = createAdminListProduct(['name' => 'Large']);
        attachAdminBusiness($small, $small->getAttribute('user_id'), ['restaurant' => true], 'small');
        attachAdminBusiness($large, $large->getAttribute('user_id'), ['restaurant' => true], 'large');

        $response = $test->getJson('/api/v2/products/admin/?business_size=large')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Large');
    });

    it('applies combined filters with AND logic', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $type = createAdminListProductType(['code' => 'nfc-card']);
        $owner = $test->create_user(['email' => 'and-logic@example.com']);

        $match = createAdminListProduct([
            'product_type_id' => $type->id,
            'user_id' => $owner->id,
            'name' => 'Match Product',
            'model' => 'nfc',
            'configuration_status' => ConfigurationStatus::COMPLETED,
            'active' => true,
            'target_url' => 'https://example.com/match',
        ]);
        attachAdminBusiness($match, $match->getAttribute('user_id'), ['restaurant' => true], 'small');

        $noMatch = createAdminListProduct([
            'product_type_id' => $type->id,
            'user_id' => $owner->id,
            'name' => 'No Match Product',
            'model' => 'nfc',
            'configuration_status' => ConfigurationStatus::ASSIGNED,
            'active' => true,
            'target_url' => 'https://example.com/match',
        ]);
        attachAdminBusiness($noMatch, $noMatch->getAttribute('user_id'), ['restaurant' => true], 'small');

        $response = $test->getJson('/api/v2/products/admin/?product_type_code=nfc-card&name=Match%20Product&user_email=and-logic@example.com&configuration_status=completed&active=true&target_url=match&business_type=restaurant&business_size=small')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Match Product');
    });

    it('treats empty string filters as null', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'One']);
        createAdminListProduct(['name' => 'Two']);

        $response = $test->getJson('/api/v2/products/admin/?name=&user_email=&target_url=&business_size=')->assertOk();

        expect($response->json('meta.total'))->toBe(2);
    });

    it('sorts by name asc', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Zebra']);
        createAdminListProduct(['name' => 'Apple']);
        createAdminListProduct(['name' => 'Mango']);

        $response = $test->getJson('/api/v2/products/admin/?sort_by=name&sort_dir=asc')->assertOk();

        expect(array_slice(responseNames($response), 0, 3))
            ->toBe(['Apple', 'Mango', 'Zebra']);
    });

    it('sorts by usage desc', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Low', 'usage' => 5]);
        createAdminListProduct(['name' => 'High', 'usage' => 50]);
        createAdminListProduct(['name' => 'Mid', 'usage' => 10]);

        $response = $test->getJson('/api/v2/products/admin/?sort_by=usage&sort_dir=desc')->assertOk();

        expect(array_slice(responseUsages($response), 0, 3))
            ->toBe([50, 10, 5]);
    });

    it('sorts by active asc', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Active', 'active' => true]);
        createAdminListProduct(['name' => 'Inactive', 'active' => false]);

        $response = $test->getJson('/api/v2/products/admin/?sort_by=active&sort_dir=asc')->assertOk();

        expect(array_slice(responseActives($response), 0, 2))
            ->toBe([false, true]);
    });

    it('sorts configuration_status using binary case and assigned_at desc', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Assigned', 'configuration_status' => ConfigurationStatus::ASSIGNED, 'assigned_at' => CarbonImmutable::now()->subDay()]);
        createAdminListProduct(['name' => 'Completed New', 'configuration_status' => ConfigurationStatus::COMPLETED, 'assigned_at' => CarbonImmutable::now()]);
        createAdminListProduct(['name' => 'Completed Old', 'configuration_status' => ConfigurationStatus::COMPLETED, 'assigned_at' => CarbonImmutable::now()->subHour()]);
        createAdminListProduct(['name' => 'Business', 'configuration_status' => ConfigurationStatus::BUSINESS_SET, 'assigned_at' => CarbonImmutable::now()->subMinutes(30)]);

        $response = $test->getJson('/api/v2/products/admin/?sort_by=configuration_status&sort_dir=asc')->assertOk();

        expect(array_slice(responseNames($response), 0, 4))
            ->toBe(['Completed New', 'Completed Old', 'Business', 'Assigned']);
    });

    it('sorts by model desc', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'A', 'model' => 'nfc']);
        createAdminListProduct(['name' => 'B', 'model' => 'link']);
        createAdminListProduct(['name' => 'C', 'model' => 'card']);

        $response = $test->getJson('/api/v2/products/admin/?sort_by=model&sort_dir=desc')->assertOk();

        expect(array_slice(responseModels($response), 0, 3))
            ->toBe(['nfc', 'link', 'card']);
    });

    it('sorts by assigned_at desc by default', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Oldest', 'assigned_at' => CarbonImmutable::now()->subDays(2)]);
        createAdminListProduct(['name' => 'Newest', 'assigned_at' => CarbonImmutable::now()]);
        createAdminListProduct(['name' => 'Middle', 'assigned_at' => CarbonImmutable::now()->subDay()]);

        $response = $test->getJson('/api/v2/products/admin/')->assertOk();

        expect(array_slice(responseNames($response), 0, 3))
            ->toBe(['Newest', 'Middle', 'Oldest']);
    });

    it('sorts by created_at asc', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $old = createAdminListProduct(['name' => 'Old']);
        $mid = createAdminListProduct(['name' => 'Mid']);
        $new = createAdminListProduct(['name' => 'New']);

        $old->forceFill(['created_at' => CarbonImmutable::create(2025, 1, 1, 0, 0, 0, 'UTC')])->save();
        $mid->forceFill(['created_at' => CarbonImmutable::create(2025, 1, 2, 0, 0, 0, 'UTC')])->save();
        $new->forceFill(['created_at' => CarbonImmutable::create(2025, 1, 3, 0, 0, 0, 'UTC')])->save();

        $response = $test->getJson('/api/v2/products/admin/?sort_by=created_at&sort_dir=asc')->assertOk();

        expect(array_slice(responseNames($response), 0, 3))
            ->toBe(['Old', 'Mid', 'New']);
    });

    it('invalid sort_by falls back to assigned_at desc', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Oldest', 'assigned_at' => CarbonImmutable::now()->subDays(2)]);
        createAdminListProduct(['name' => 'Newest', 'assigned_at' => CarbonImmutable::now()]);
        createAdminListProduct(['name' => 'Middle', 'assigned_at' => CarbonImmutable::now()->subDay()]);

        $response = $test->getJson('/api/v2/products/admin/?sort_by=unknown&sort_dir=asc')->assertOk();

        expect(array_slice(responseNames($response), 0, 3))
            ->toBe(['Newest', 'Middle', 'Oldest']);
    });

    it('invalid sort_dir falls back to desc', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Apple']);
        createAdminListProduct(['name' => 'Mango']);
        createAdminListProduct(['name' => 'Zebra']);

        $response = $test->getJson('/api/v2/products/admin/?sort_by=name&sort_dir=sideways')->assertOk();

        expect(array_slice(responseNames($response), 0, 3))
            ->toBe(['Zebra', 'Mango', 'Apple']);
    });

    it('clamps per_page between 1 and 100', function (): void {
        /** @var TestCase $test */
        $test = $this;
        foreach (range(1, 101) as $index) {
            createAdminListProduct(['name' => 'Product ' . $index]);
        }

        $high = $test->getJson('/api/v2/products/admin/?per_page=200')->assertOk();
        $low = $test->getJson('/api/v2/products/admin/?per_page=0')->assertOk();

        expect($high->json('meta.per_page'))->toBe(100)
            ->and($high->json('data'))->toHaveCount(100)
            ->and($low->json('meta.per_page'))->toBe(1)
            ->and($low->json('data'))->toHaveCount(1);
    });

    it('excludes linked secondary and soft-deleted products', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $primary = createAdminListProduct(['name' => 'Primary']);
        createAdminListProduct(['name' => 'Secondary', 'linked_to_product_id' => $primary->id]);
        $deleted = createAdminListProduct(['name' => 'Deleted']);
        $deleted->delete();

        $response = $test->getJson('/api/v2/products/admin/')->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('data.0.name'))->toBe('Primary');
    });

    it('includes business paired_product and user as nullable enriched fields', function (): void {
        /** @var TestCase $test */
        $test = $this;
        $owner = $test->create_user(['name' => 'Owner Name', 'email' => 'owner-enriched@example.com']);
        $primary = createAdminListProduct([
            'user_id' => $owner->id,
            'name' => 'Primary',
            'assigned_at' => CarbonImmutable::create(2025, 10, 15, 14, 30, 0, 'UTC'),
        ]);
        attachAdminBusiness($primary, $owner->id, ['restaurant' => true], 'small');
        $paired = createAdminListProduct([
            'user_id' => $owner->id,
            'name' => 'Paired',
            'model' => 'qr',
            'linked_to_product_id' => $primary->id,
        ]);

        $response = $test->getJson('/api/v2/products/admin/?name=Primary')->assertOk();

        expect($response->json('data.0.business'))->toBe([
            'types' => ['restaurant' => true],
            'size' => 'small',
        ])->and($response->json('data.0.paired_product'))->toBe([
            'id' => $paired->id,
            'name' => 'Paired',
            'model' => 'qr',
        ])->and($response->json('data.0.user'))->toBe([
            'id' => $owner->id,
            'name' => 'Owner Name',
            'email' => 'owner-enriched@example.com',
        ])->and($response->json('data.0.assigned_at'))->toBe('2025-10-15T14:30:00+00:00')
            ->and($response->json('data.0.created_at'))->not->toBeNull();
    });

    it('returns null enriched fields when optional relations are missing', function (): void {
        /** @var TestCase $test */
        $test = $this;
        createAdminListProduct(['name' => 'Standalone', 'user_id' => null]);

        $response = $test->getJson('/api/v2/products/admin/?name=Standalone')->assertOk();

        expect($response->json('data.0.business'))->toBeNull()
            ->and($response->json('data.0.paired_product'))->toBeNull()
            ->and($response->json('data.0.user'))->toBeNull();
    });
});
