<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\Entities\Product;
use Src\Products\Infrastructure\Persistence\EloquentProductRepository;

uses(RefreshDatabase::class);

describe('EloquentProductRepository::bulkInsertAndReturnIds', function () {
    it('returns ids in insertion order across chunk boundaries (chunkSize: 2)', function () {
        $this->seed(ProductConfigurationStatusSeeder::class);

        $productTypeId = DB::table('product_types')->insertGetId([
            'code' => 'CHUNK-TEST',
            'name' => 'Chunk Test Type',
            'description' => null,
            'image_ref' => 'CHUNK-TEST',
            'primary_model' => 'P-CT',
            'secondary_model' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $products = [];

        for ($index = 0; $index < 5; $index++) {
            $products[] = Product::create(
                productTypeId: $productTypeId,
                model: 'P-CT',
                password: 'chunk-pwd-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            );
        }

        /** @var EloquentProductRepository $repository */
        $repository = app(EloquentProductRepository::class);

        $ids = $repository->bulkInsertAndReturnIds($products, chunkSize: 2);

        expect($ids)->toHaveCount(5);

        foreach ($products as $index => $product) {
            $row = DB::table('products')->where('id', $ids[$index])->first();

            expect($row)->not->toBeNull();
            expect($row->password)->toBe($product->password->value);
        }
    });
});
