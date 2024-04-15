<?php

namespace App\UseCases\Product;

use App\DTO\ProductDto;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class UCAssignToUser implements UseCaseContract
{
    public function __construct()
    {
    }

    /**
     * UseCase: Assign a product to given user
     *
     * @param array{productId: int, userId: int}|null $data
     * @param array|null $opts
     * @return ProductDto
     * @throws ProductNotFoundException
     */
    public function run(array $data = null, ?array $opts = null): ProductDto
    {
        $productId = $data['productId'];
        $userId = $data['userId'];

        return $this->assignToCurrentUser($productId, $userId);
    }

    /**
     * Assign a product to given user
     *
     * @param int $productId
     * @param string $userId
     * @return ProductDto
     * @throws ProductNotFoundException
     * @throws Exception
     */
    private function assignToCurrentUser(int $productId, string $userId): ProductDto
    {
        try {
            $product = Product::findById(
                $productId,
            );

            $product->update([
                'user_id' => $userId,
            ]);

            return ProductDto::fromModel($product);
        } catch (ModelNotFoundException $e) {
            throw new ProductNotFoundException();
        }
    }
}
