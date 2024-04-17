<?php

namespace App\UseCases\Product\Filtering;

use App\DTO\ProductDto;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

readonly class FindByIdUC implements UseCaseContract
{
    public function __construct()
    {
    }

    /**
     * Retrieves a product by its ID
     *
     * @param array{id: int}|null $data
     * @throws ProductNotFoundException|Exception
     */
    public function run(?array $data = null, ?array $opts = null): ProductDto
    {
        $productId = $data['id'];

        return $this->findById($productId, $opts);
    }

    /**
     * Retrieves a product by its ID
     *
     * @param int $id
     * @param array|null $opts
     * @return ProductDto
     * @throws ProductNotFoundException
     * @throws Exception
     */
    private function findById(int $id, ?array $opts = null): ProductDto {
        if (!isset($id)) {
            throw new Exception('invalid_nfc_id');
        }

        try{
            $product = Product::findById($id);

            return ProductDto::fromModel($product);
        } catch (ModelNotFoundException $e){
            throw new ProductNotFoundException();
        }
    }
}
