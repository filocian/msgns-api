<?php

namespace App\UseCases\Product;

use App\DTO\ProductDto;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\Product;
use App\Services\AuthService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class UCActivateProduct implements UseCaseContract
{
    public function __construct(
        private readonly AuthService $authService,
    )
    {
    }

    /**
     * Activate a product based on product id and its password
     *
     * @param array{id: int}|null $data
     * @param array|null $opts
     * @return ProductDto
     * @throws ProductNotFoundException
     */
    public function run(array $data = null, ?array $opts = null): ProductDto
    {
        $productId = $data['id'];

        return $this->activateProduct($productId);

    }

    /**
     * Activates a product
     *
     * @param int $productId
     * @return ProductDto
     * @throws ProductNotFoundException
     * @throws Exception
     */
    private function activateProduct(int $productId): ProductDto
    {
        $userId = $this->authService->id();

        if ($userId == null) {
            throw new Exception('invalid_user');
        }

        try {
            $product = Product::findById(
                $productId,
            );

            $product->update([
                'active' => true
            ]);

            return ProductDto::fromModel($product);
        } catch (ModelNotFoundException $e) {
            throw new ProductNotFoundException();
        }
    }
}
