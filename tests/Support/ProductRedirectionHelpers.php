<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

if (!function_exists('createRedirectionProductType')) {
    function createRedirectionProductType(): int
    {
        $uid = uniqid();

        return DB::table('product_types')->insertGetId([
            'code' => 'TYPE-' . $uid,
            'name' => 'Type ' . $uid,
            'image_ref' => 'TYPE-' . $uid,
            'primary_model' => 'google',
            'secondary_model' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

if (!function_exists('createRedirectionProduct')) {
    /**
     * @param array<string, mixed> $overrides
     * @return array{id: int, data: array<string, mixed>}
     */
    function createRedirectionProduct(array $overrides = []): array
    {
        $productTypeId = $overrides['product_type_id'] ?? createRedirectionProductType();
        $password = $overrides['password'] ?? 'test-pass';
        $defaults = [
            'product_type_id' => $productTypeId,
            'user_id' => null,
            'model' => 'google',
            'linked_to_product_id' => null,
            'password' => $password,
            'target_url' => 'https://google.com',
            'usage' => 0,
            'name' => 'Test Product',
            'description' => null,
            'active' => true,
            'configuration_status' => 'completed',
            'assigned_at' => null,
            'size' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];

        $data = array_merge($defaults, $overrides);
        $id = DB::table('products')->insertGetId($data);

        return ['id' => $id, 'data' => $data];
    }
}
