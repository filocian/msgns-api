<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductActivated;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Infrastructure\Persistence\EloquentProductRepository;
use Src\Shared\Core\Bus\EventBus;
uses(RefreshDatabase::class);

it('publishes only DomainEvent instances released by the product after save', function () {
    $this->seed(ProductConfigurationStatusSeeder::class);

    $productTypeId = DB::table('product_types')->insertGetId([
        'code' => 'TYPE-' . uniqid(),
        'name' => 'Type ' . uniqid(),
        'image_ref' => 'TYPE-' . uniqid(),
        'primary_model' => 'ModelA',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $product = Product::create(
        productTypeId: $productTypeId,
        model: 'ModelA',
        password: 'password-' . uniqid(),
    );

    $domainEvent = new ProductActivated(productId: 999);
    $product->recordEvent($domainEvent);
    $product->recordEvent(new stdClass());

    $eventBus = Mockery::mock(EventBus::class);
    $eventBus->shouldReceive('publish')
        ->once()
        ->with($domainEvent);

    $repository = new EloquentProductRepository($eventBus);
    $savedProduct = $repository->save($product);

    expect($savedProduct->id)->toBeGreaterThan(0)
        ->and($product->releaseEvents())->toBe([]);
});

it('does not publish anything when no events were recorded', function () {
    $this->seed(ProductConfigurationStatusSeeder::class);

    $productTypeId = DB::table('product_types')->insertGetId([
        'code' => 'TYPE-' . uniqid(),
        'name' => 'Type ' . uniqid(),
        'image_ref' => 'TYPE-' . uniqid(),
        'primary_model' => 'ModelA',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $productId = DB::table('products')->insertGetId([
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'ModelA',
        'linked_to_product_id' => null,
        'password' => 'password-' . uniqid(),
        'target_url' => null,
        'usage' => 0,
        'name' => 'Product Name',
        'description' => null,
        'active' => false,
        'configuration_status' => ConfigurationStatus::NOT_STARTED,
        'assigned_at' => null,
        'size' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $timestamp = new \DateTimeImmutable();

    $product = Product::fromPersistence(
        id: $productId,
        productTypeId: $productTypeId,
        userId: null,
        model: 'ModelA',
        linkedToProductId: null,
        password: 'password-' . uniqid(),
        targetUrl: null,
        usage: 0,
        name: 'Product Name',
        description: null,
        active: false,
        configurationStatus: ConfigurationStatus::from(ConfigurationStatus::NOT_STARTED),
        assignedAt: null,
        size: null,
        createdAt: $timestamp,
        updatedAt: $timestamp,
        deletedAt: null,
    );

    $eventBus = Mockery::mock(EventBus::class);
    $eventBus->shouldNotReceive('publish');

    $repository = new EloquentProductRepository($eventBus);
    $repository->save($product);

    expect($product->releaseEvents())->toBe([]);
});

afterEach(fn () => Mockery::close());
