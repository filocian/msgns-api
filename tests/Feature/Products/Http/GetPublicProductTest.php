<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../../../Support/ProductRedirectionHelpers.php';

beforeEach(function () {
    $this->seed(ProductConfigurationStatusSeeder::class);
});

describe('GET /api/v2/products/{id}/{password}', function () {
    it('returns 200 with product data for valid id and password', function () {
        $product = createRedirectionProduct(['password' => 'my-pass']);

        $this->getJson("/api/v2/products/{$product['id']}/my-pass")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'model',
                    'name',
                    'configurationStatus',
                    'productType' => [
                        'id',
                        'code',
                        'primaryModel',
                    ],
                ],
            ]);
    });

    it('returns 404 for wrong password', function () {
        $product = createRedirectionProduct(['password' => 'correct']);

        $this->getJson("/api/v2/products/{$product['id']}/wrong-pass")
            ->assertNotFound();
    });

    it('returns 404 for non-existent id', function () {
        $this->getJson('/api/v2/products/999999/any-pass')
            ->assertNotFound();
    });

    it('returns 404 for soft-deleted product', function () {
        $product = createRedirectionProduct(['password' => 'soft-pass']);
        DB::table('products')->where('id', $product['id'])->update(['deleted_at' => now()]);

        $this->getJson("/api/v2/products/{$product['id']}/soft-pass")
            ->assertNotFound();
    });

    it('returns 404 when the product type is missing', function () {
        $product = createRedirectionProduct();
        // Simulate orphan: set product_type_id on the product row to a non-existent ID.
        // SQLite FK constraints cannot be changed inside a transaction, so we update the
        // product to reference an ID that has no matching product_type row instead.
        // To bypass FK on the UPDATE we commit the current test transaction, toggle FK off,
        // make the change on a fresh connection, then rely on RefreshDatabase's migrate:fresh
        // for cleanup (in-memory DB is wiped between runs).
        DB::rollBack(); // exit the RefreshDatabase transaction
        $pdo = DB::connection()->getPdo();
        $pdo->exec('PRAGMA foreign_keys = OFF');
        $pdo->exec('DELETE FROM product_types WHERE id = ' . (int) $product['data']['product_type_id']);
        $pdo->exec('PRAGMA foreign_keys = ON');
        DB::beginTransaction(); // re-enter so RefreshDatabase can roll back at the end

        $this->getJson("/api/v2/products/{$product['id']}/test-pass")
            ->assertNotFound();
    });

    it('does not require authentication', function () {
        $product = createRedirectionProduct(['password' => 'public-pass']);

        $this->getJson("/api/v2/products/{$product['id']}/public-pass")
            ->assertOk();
    });

    it('does not expose sensitive fields in response', function () {
        $product = createRedirectionProduct(['password' => 'secret-pass']);

        $response = $this->getJson("/api/v2/products/{$product['id']}/secret-pass")
            ->assertOk();

        $data = $response->json('data');

        expect($data)->not->toHaveKey('password')
            ->and($data)->not->toHaveKey('userId')
            ->and($data)->not->toHaveKey('usage');

        $response
            ->assertJsonMissingPath('data.linkedToProductId')
            ->assertJsonMissingPath('data.createdAt')
            ->assertJsonMissingPath('data.updatedAt')
            ->assertJsonMissingPath('data.deletedAt');
    });
});
