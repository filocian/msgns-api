<?php

declare(strict_types=1);

use App\Models\Product;
use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Instagram\Infrastructure\Adapters\EloquentInstagramProductConfiguration;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(ProductConfigurationStatusSeeder::class));

describe('EloquentInstagramProductConfiguration', function (): void {

    it('returns the instagram_account_id string when the column is set', function (): void {
        $product = Product::factory()->create([
            'instagram_account_id' => '17841400000000010',
        ]);

        $adapter = new EloquentInstagramProductConfiguration();

        expect($adapter->getInstagramAccountIdForProduct($product->id))
            ->toBe('17841400000000010');
    });

    it('returns null when the product exists but instagram_account_id is null', function (): void {
        $product = Product::factory()->create([
            'instagram_account_id' => null,
        ]);

        $adapter = new EloquentInstagramProductConfiguration();

        expect($adapter->getInstagramAccountIdForProduct($product->id))->toBeNull();
    });

    it('returns null when the product does not exist', function (): void {
        $adapter = new EloquentInstagramProductConfiguration();

        expect($adapter->getInstagramAccountIdForProduct(999_999))->toBeNull();
    });
});
