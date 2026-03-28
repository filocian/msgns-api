<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Ports\AnalyticsPort;

it('tracks PRODUCT_ENABLED only after the wrapping transaction commits', function () {
    $this->seed(ProductConfigurationStatusSeeder::class);
    config()->set('queue.default', 'sync');
    config()->set('queue.connections.sync.after_commit', true);

    $analytics = new class implements AnalyticsPort {
        /** @var list<array{event: string, properties: array<string, mixed>}> */
        public array $tracked = [];

        public function track(string $event, array $properties = []): void
        {
            $this->tracked[] = ['event' => $event, 'properties' => $properties];
        }

        public function identify(string $userId, array $properties = []): void {}

        public function setGroup(string $groupKey, string $groupId, array $properties = []): void {}

        public function setSystemAlias(string $alias): void {}

        public function info(string $eventName, string $title, string $message, ?array $data = null): void {}

        public function warn(string $eventName, string $title, string $message, ?array $data = null): void {}

        public function error(string $eventName, string $title, string $message, ?array $data = null): void {}

        public function critical(string $eventName, string $title, string $message, ?array $data = null): void {}
    };

    $this->app->instance(AnalyticsPort::class, $analytics);

    $user = $this->create_user(['email' => 'analytics-products@example.com']);
    $this->actingAs($user, 'stateful-api');

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
        'name' => 'Product ' . uniqid(),
        'description' => null,
        'active' => false,
        'configuration_status' => ConfigurationStatus::NOT_STARTED,
        'assigned_at' => null,
        'size' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ]);

    DB::transaction(function () use ($productId, $analytics): void {
        $this->postJson("/api/v2/products/{$productId}/activate")
            ->assertOk();

        expect($analytics->tracked)->toHaveCount(0);
    });

    expect($analytics->tracked)->toHaveCount(1)
        ->and($analytics->tracked[0]['event'])->toBe('PRODUCT_ENABLED')
        ->and($analytics->tracked[0]['properties'])->toBe([
            'product_id' => $productId,
            'active' => true,
        ]);
});

it('does not track PRODUCT_ENABLED when the wrapping transaction rolls back', function () {
    $this->seed(ProductConfigurationStatusSeeder::class);
    config()->set('queue.default', 'sync');
    config()->set('queue.connections.sync.after_commit', true);

    $analytics = new class implements AnalyticsPort {
        /** @var list<array{event: string, properties: array<string, mixed>}> */
        public array $tracked = [];

        public function track(string $event, array $properties = []): void
        {
            $this->tracked[] = ['event' => $event, 'properties' => $properties];
        }

        public function identify(string $userId, array $properties = []): void {}

        public function setGroup(string $groupKey, string $groupId, array $properties = []): void {}

        public function setSystemAlias(string $alias): void {}

        public function info(string $eventName, string $title, string $message, ?array $data = null): void {}

        public function warn(string $eventName, string $title, string $message, ?array $data = null): void {}

        public function error(string $eventName, string $title, string $message, ?array $data = null): void {}

        public function critical(string $eventName, string $title, string $message, ?array $data = null): void {}
    };

    $this->app->instance(AnalyticsPort::class, $analytics);

    $user = $this->create_user(['email' => 'analytics-products-rollback@example.com']);
    $this->actingAs($user, 'stateful-api');

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
        'name' => 'Product ' . uniqid(),
        'description' => null,
        'active' => false,
        'configuration_status' => ConfigurationStatus::NOT_STARTED,
        'assigned_at' => null,
        'size' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ]);

    try {
        DB::transaction(function () use ($productId, $analytics): void {
            $this->postJson("/api/v2/products/{$productId}/activate")
                ->assertOk();

            expect($analytics->tracked)->toHaveCount(0);

            throw new RuntimeException('force rollback');
        });
    } catch (RuntimeException) {
        // expected rollback for this scenario
    }

    expect($analytics->tracked)->toHaveCount(0);
});
