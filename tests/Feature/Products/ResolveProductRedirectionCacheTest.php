<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Src\Products\Domain\Events\ProductConfigStatusChanged;
use Src\Products\Domain\Events\ProductRestored;
use Src\Products\Domain\Events\ProductSoftDeleted;
use Src\Products\Domain\Events\ProductUnlinked;
use Src\Products\Domain\Ports\ProductUsagePort;

beforeEach(function () {
    $this->seed(ProductConfigurationStatusSeeder::class);
    config()->set('queue.default', 'sync');

    app()->bind(ProductUsagePort::class, static fn (): ProductUsagePort => new class implements ProductUsagePort
    {
        public function writeUsageEvent(int $productId, int $userId, string $productName, DateTimeImmutable $timestamp): void {}
        public function queryProductUsage(int $productId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array { return []; }
        public function deleteProductUsage(int $productId): void {}
    });
});

function createCachedRedirectionProduct(array $overrides = []): int
{
    $typeId = DB::table('product_types')->insertGetId([
        'code' => 'TYPE-' . uniqid(),
        'name' => 'Type ' . uniqid(),
        'image_ref' => 'TYPE-' . uniqid(),
        'primary_model' => 'google',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $typeId,
        'user_id' => null,
        'model' => 'google',
        'linked_to_product_id' => null,
        'password' => 'secret',
        'target_url' => 'https://example.com',
        'usage' => 0,
        'name' => 'Cached Product',
        'description' => null,
        'active' => true,
        'configuration_status' => 'completed',
        'assigned_at' => null,
        'size' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ], $overrides));
}

describe('ResolveProductRedirection cache flow', function () {
    it('caches successful scans and increments usage on every success', function () {
        $productId = createCachedRedirectionProduct();

        $this->getJson("/api/v2/products/{$productId}/secret/redirection-target")->assertOk();
        expect(Cache::get("products:redirection:{$productId}"))->not->toBeNull();
        expect(DB::table('products')->where('id', $productId)->value('usage'))->toBe(1);

        $this->getJson("/api/v2/products/{$productId}/secret/redirection-target")->assertOk();
        expect(DB::table('products')->where('id', $productId)->value('usage'))->toBe(2);
    });

    it('invalidates cache after product mutations', function () {
        $this->user = $this->create_user(['email' => 'cache-tests@example.com']);
        $this->actingAs($this->user, 'stateful-api');
        $productId = createCachedRedirectionProduct(['target_url' => 'https://one.example.com']);

        $this->getJson("/api/v2/products/{$productId}/secret/redirection-target")->assertOk();
        expect(Cache::get("products:redirection:{$productId}"))->not->toBeNull();

        $this->patchJson("/api/v2/products/{$productId}/target-url", ['target_url' => 'https://two.example.com'])->assertOk();
        expect(Cache::get("products:redirection:{$productId}"))->toBeNull();

        $this->getJson("/api/v2/products/{$productId}/secret/redirection-target")
            ->assertOk()
            ->assertJsonPath('data.url', 'https://two.example.com');
    });

    it('invalidates cache for config status, soft delete, restore, and unlink events', function (callable $eventFactory) {
        $productId = createCachedRedirectionProduct();

        $this->getJson("/api/v2/products/{$productId}/secret/redirection-target")->assertOk();
        expect(Cache::get("products:redirection:{$productId}"))->not->toBeNull();

        Event::dispatch($eventFactory($productId));

        expect(Cache::get("products:redirection:{$productId}"))->toBeNull();
    })->with([
        'config-status-changed' => static fn (int $productId): ProductConfigStatusChanged => new ProductConfigStatusChanged($productId, 'assigned', 'completed'),
        'soft-deleted' => static fn (int $productId): ProductSoftDeleted => new ProductSoftDeleted($productId),
        'restored' => static fn (int $productId): ProductRestored => new ProductRestored($productId),
        'unlinked' => static fn (int $productId): ProductUnlinked => new ProductUnlinked($productId, 77),
    ]);

    it('does not increment usage when the scan fails', function () {
        $productId = createCachedRedirectionProduct();

        $this->getJson("/api/v2/products/{$productId}/wrong/redirection-target")->assertNotFound();

        expect(DB::table('products')->where('id', $productId)->value('usage'))->toBe(0);
    });
});
