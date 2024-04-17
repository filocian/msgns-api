<?php

namespace App\UseCases\Product\Configuration;

use App\DTO\ProductDto;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\Product;
use Exception;


readonly class BulkConfigureUC implements UseCaseContract
{
    public function __construct()
    {
    }

    /**
     * UseCase: Bulk activation of a given productId list
     *
     * @param array{id: int[]}|null $data
     * @param array|null $opts
     * @return ProductDto[]
     * @throws ProductNotFoundException
     */
    public function run(array $data = null, ?array $opts = null): array
    {
        $productsId = $data['id'];

        return $this->bulkActivateProduct($productsId);

    }

    /**
     * Bulk activation of a given productId list
     *
     * @param int[] $productsId
     * @return ProductDto[]
     * @throws ProductNotFoundException
     * @throws Exception
     */
    private function bulkActivateProduct(array $productsId): array
    {
        $productsCollection = Product::whereIn('id', $productsId);

        if ($productsCollection->isEmpty()) {
            throw new ProductNotFoundException();
        }

        $productsCollection->update([
            'active' => true
        ]);

        $productsCollection->refresh();

        return $productsCollection->map(function ($product) {
            ProductDto::fromModel($product);
        });
    }
}
