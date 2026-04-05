<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductBusiness;
use App\Models\ProductType;
use Carbon\CarbonImmutable;
use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Infrastructure\Persistence\EloquentProductRepository;
use Src\Shared\Core\Bus\EventBus;
use Tests\TestCase;

uses(RefreshDatabase::class);

function makeAdminRepository(): EloquentProductRepository
{
    /** @var Mockery\MockInterface&EventBus $eventBus */
    $eventBus = Mockery::mock(EventBus::class);
    $eventBus->shouldIgnoreMissing();

    return new EloquentProductRepository($eventBus);
}

/**
 * @param array<string, mixed> $overrides
 */
function createAdminRepositoryProductType(array $overrides = []): ProductType
{
    /** @var ProductType $productType */
    $productType = ProductType::factory()->create(array_merge([
        'code' => 'repo-type-' . str()->lower(str()->random(8)),
        'name' => 'Repo Type ' . str()->lower(str()->random(4)),
        'primary_model' => 'nfc',
    ], $overrides));

    return $productType;
}

/**
 * @param array<string, mixed> $overrides
 */
function createAdminRepositoryProduct(array $overrides = []): Product
{
    $productTypeId = array_key_exists('product_type_id', $overrides)
        ? $overrides['product_type_id']
        : createAdminRepositoryProductType()->id;

    /** @var Product $product */
    $product = Product::factory()->create(array_merge([
        'product_type_id' => $productTypeId,
        'model' => 'nfc',
        'linked_to_product_id' => null,
        'target_url' => 'https://example.com/default',
        'active' => true,
        'configuration_status' => ConfigurationStatus::ASSIGNED,
        'assigned_at' => CarbonImmutable::create(2025, 3, 15, 10, 0, 0, 'UTC'),
        'usage' => 0,
        'name' => 'Repository Product',
    ], $overrides));

    return $product;
}

/**
 * @param array<string, bool> $types
 */
function attachAdminRepositoryBusiness(Product $product, ?int $userId = null, array $types = ['restaurant' => true], ?string $size = 'small'): ProductBusiness
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
    /** @var TestCase $this */
    $this->seed(ProductConfigurationStatusSeeder::class);
});

afterEach(fn () => Mockery::close());

describe('EloquentProductRepository::listForAdmin', function () {
    it('applies key admin filters while preserving enriched response fields', function () {
        /** @var TestCase $this */
        $type = createAdminRepositoryProductType(['code' => 'nfc-card']);
        $owner = $this->create_user(['name' => 'Owner Name', 'email' => 'john_doe@example.com']);
        $noiseUser = $this->create_user(['email' => 'johnXdoe@example.com']);
        $repository = makeAdminRepository();

        $matching = createAdminRepositoryProduct([
            'product_type_id' => $type->id,
            'user_id' => $owner->id,
            'name' => '100% Real Card',
            'target_url' => 'https://shop.example.com/100%off',
            'configuration_status' => ConfigurationStatus::COMPLETED,
            'usage' => 50,
            'assigned_at' => CarbonImmutable::create(2025, 3, 15, 10, 0, 0, 'UTC'),
        ]);
        attachAdminRepositoryBusiness($matching, $owner->id, ['restaurant' => true], 'small');

        $paired = createAdminRepositoryProduct([
            'user_id' => $owner->id,
            'name' => 'Paired Product',
            'model' => 'qr',
            'linked_to_product_id' => $matching->id,
        ]);

        createAdminRepositoryProduct([
            'product_type_id' => $type->id,
            'user_id' => $noiseUser->id,
            'name' => '100X Real Card',
            'target_url' => 'https://shop.example.com/1000off',
            'configuration_status' => ConfigurationStatus::COMPLETED,
        ]);

        createAdminRepositoryProduct([
            'product_type_id' => $type->id,
            'user_id' => $owner->id,
            'name' => 'Excluded By Status',
            'configuration_status' => ConfigurationStatus::ASSIGNED,
        ]);

        $deleted = createAdminRepositoryProduct([
            'product_type_id' => $type->id,
            'user_id' => $owner->id,
            'name' => 'Deleted Match',
            'target_url' => 'https://shop.example.com/100%off',
            'configuration_status' => ConfigurationStatus::COMPLETED,
        ]);
        $deleted->delete();

        $result = $repository->listForAdmin([
            'productTypeCode' => 'nfc-card',
            'name' => '100%',
            'userEmail' => 'john_doe',
            'assignedAtFrom' => '2025-03-10',
            'assignedAtTo' => '2025-03-20',
            'configurationStatus' => ConfigurationStatus::COMPLETED,
            'active' => true,
            'targetUrl' => '100%off',
            'businessType' => 'restaurant',
            'businessSize' => 'small',
            'sortBy' => 'usage',
            'sortDir' => 'desc',
        ]);

        expect($result->total)->toBe(1)
            ->and($result->items)->toHaveCount(1)
            ->and($result->items[0])->toMatchArray([
                'id' => $matching->id,
                'name' => '100% Real Card',
                'usage' => 50,
                'configuration_status' => ConfigurationStatus::COMPLETED,
                'product_type' => [
                    'id' => $type->id,
                    'code' => 'nfc-card',
                    'name' => (string) $type->getAttribute('name'),
                ],
                'business' => [
                    'types' => ['restaurant' => true],
                    'size' => 'small',
                ],
                'paired_product' => [
                    'id' => $paired->id,
                    'name' => 'Paired Product',
                    'model' => 'qr',
                ],
                'user' => [
                    'id' => $owner->id,
                    'name' => 'Owner Name',
                    'email' => 'john_doe@example.com',
                ],
            ]);
    });

    it('clamps pagination and applies configuration status binary sorting', function () {
        $repository = makeAdminRepository();
        createAdminRepositoryProduct([
            'name' => 'Assigned Product',
            'configuration_status' => ConfigurationStatus::ASSIGNED,
            'assigned_at' => CarbonImmutable::create(2025, 4, 1, 9, 0, 0, 'UTC'),
        ]);
        createAdminRepositoryProduct([
            'name' => 'Completed New',
            'configuration_status' => ConfigurationStatus::COMPLETED,
            'assigned_at' => CarbonImmutable::create(2025, 4, 3, 9, 0, 0, 'UTC'),
        ]);
        createAdminRepositoryProduct([
            'name' => 'Completed Old',
            'configuration_status' => ConfigurationStatus::COMPLETED,
            'assigned_at' => CarbonImmutable::create(2025, 4, 2, 9, 0, 0, 'UTC'),
        ]);

        $clampedLow = $repository->listForAdmin([
            'perPage' => 0,
            'sortBy' => 'configuration_status',
            'sortDir' => 'asc',
        ]);

        $clampedHigh = $repository->listForAdmin([
            'perPage' => 200,
            'sortBy' => 'configuration_status',
            'sortDir' => 'sideways',
        ]);

        expect($clampedLow->perPage)->toBe(1)
            ->and($clampedLow->items[0]['name'])->toBe('Completed New')
            ->and($clampedHigh->perPage)->toBe(100)
            ->and(array_column($clampedHigh->items, 'name'))->toBe([
                'Assigned Product',
                'Completed New',
                'Completed Old',
            ]);
    });

    it('listForAdmin interprets date-only assignedAtFrom as startOfDay in timezone', function () {
        $repository = makeAdminRepository();

        createAdminRepositoryProduct([
            'name' => 'Excluded',
            'assigned_at' => CarbonImmutable::create(2025, 1, 15, 4, 59, 59, 'UTC'),
        ]);
        createAdminRepositoryProduct([
            'name' => 'Included',
            'assigned_at' => CarbonImmutable::create(2025, 1, 15, 5, 0, 0, 'UTC'),
        ]);

        $result = $repository->listForAdmin([
            'assignedAtFrom' => '2025-01-15',
            'timezone' => 'America/New_York',
            'sortBy' => 'assigned_at',
            'sortDir' => 'asc',
        ]);

        expect($result->total)->toBe(1)
            ->and($result->items[0]['name'])->toBe('Included');
    });

    it('listForAdmin interprets date-only assignedAtTo as endOfDay in timezone', function () {
        $repository = makeAdminRepository();

        createAdminRepositoryProduct([
            'name' => 'Included',
            'assigned_at' => CarbonImmutable::create(2025, 3, 16, 3, 59, 59, 'UTC'),
        ]);
        createAdminRepositoryProduct([
            'name' => 'Excluded',
            'assigned_at' => CarbonImmutable::create(2025, 3, 16, 5, 0, 0, 'UTC'),
        ]);

        $result = $repository->listForAdmin([
            'assignedAtTo' => '2025-03-15',
            'timezone' => 'America/New_York',
            'sortBy' => 'assigned_at',
            'sortDir' => 'asc',
        ]);

        expect($result->total)->toBe(1)
            ->and($result->items[0]['name'])->toBe('Included');
    });

    it('listForAdmin normalizes ISO-8601 datetime with offset to UTC', function () {
        $repository = makeAdminRepository();

        createAdminRepositoryProduct([
            'name' => 'Included',
            'assigned_at' => CarbonImmutable::create(2025, 3, 15, 10, 0, 0, 'UTC'),
        ]);
        createAdminRepositoryProduct([
            'name' => 'Excluded',
            'assigned_at' => CarbonImmutable::create(2025, 3, 15, 9, 59, 0, 'UTC'),
        ]);

        $result = $repository->listForAdmin([
            'assignedAtFrom' => '2025-03-15T09:00:00-01:00',
            'timezone' => 'UTC',
            'sortBy' => 'assigned_at',
            'sortDir' => 'asc',
        ]);

        expect($result->total)->toBe(1)
            ->and($result->items[0]['name'])->toBe('Included');
    });

    it('listForAdmin normalizes ISO-8601 assignedAtTo with offset to UTC', function () {
        $repository = makeAdminRepository();

        createAdminRepositoryProduct([
            'name' => 'Included',
            'assigned_at' => CarbonImmutable::create(2025, 3, 16, 4, 59, 59, 'UTC'),
        ]);
        createAdminRepositoryProduct([
            'name' => 'Excluded',
            'assigned_at' => CarbonImmutable::create(2025, 3, 16, 5, 0, 0, 'UTC'),
        ]);

        $result = $repository->listForAdmin([
            'assignedAtTo' => '2025-03-15T23:59:59-05:00',
            'timezone' => 'UTC',
            'sortBy' => 'assigned_at',
            'sortDir' => 'asc',
        ]);

        expect($result->total)->toBe(1)
            ->and($result->items[0]['name'])->toBe('Included');
    });

    it('listForAdmin filters product_type_code by LIKE substring without inflating rows', function () {
        $repository = makeAdminRepository();
        $nfc = createAdminRepositoryProductType(['code' => 'nfc-card-pro']);
        $qr = createAdminRepositoryProductType(['code' => 'qr-code']);

        createAdminRepositoryProduct(['name' => 'NFC 1', 'product_type_id' => $nfc->id]);
        createAdminRepositoryProduct(['name' => 'NFC 2', 'product_type_id' => $nfc->id]);
        createAdminRepositoryProduct(['name' => 'QR', 'product_type_id' => $qr->id]);

        $result = $repository->listForAdmin([
            'productTypeCode' => 'nfc-card',
            'sortBy' => 'assigned_at',
            'sortDir' => 'asc',
        ]);

        expect($result->total)->toBe(2)
            ->and(array_column($result->items, 'name'))->toBe(['NFC 1', 'NFC 2']);
    });

    it('listForAdmin filters model by LIKE substring', function () {
        $repository = makeAdminRepository();

        createAdminRepositoryProduct(['name' => 'NFC', 'model' => 'nfc']);
        createAdminRepositoryProduct(['name' => 'NFC V2', 'model' => 'nfc-v2']);
        createAdminRepositoryProduct(['name' => 'WhatsApp', 'model' => 'whatsapp']);

        $result = $repository->listForAdmin([
            'model' => 'nfc',
            'sortBy' => 'model',
            'sortDir' => 'asc',
        ]);

        expect($result->total)->toBe(2)
            ->and(array_column($result->items, 'model'))->toBe(['nfc', 'nfc-v2']);
    });

    it('listForAdmin sort by product_type_code orders products with non-null product types', function () {
        $repository = makeAdminRepository();
        $aaa = createAdminRepositoryProductType(['code' => 'aaa']);
        $mmm = createAdminRepositoryProductType(['code' => 'mmm']);
        $zzz = createAdminRepositoryProductType(['code' => 'zzz']);

        createAdminRepositoryProduct(['name' => 'AAA', 'product_type_id' => $aaa->id]);
        createAdminRepositoryProduct(['name' => 'MMM', 'product_type_id' => $mmm->id]);
        createAdminRepositoryProduct(['name' => 'ZZZ', 'product_type_id' => $zzz->id]);

        $result = $repository->listForAdmin([
            'sortBy' => 'product_type_code',
            'sortDir' => 'asc',
            'perPage' => 100,
        ]);

        expect($result->total)->toBe(3)
            ->and(array_column($result->items, 'name'))->toBe(['AAA', 'MMM', 'ZZZ']);
    });
});
