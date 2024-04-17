<?php

namespace App\UseCases\Product\Activation;

use App\DTO\ProductDto;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\Product;
use App\Services\AuthService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;


readonly class DeactivateUC implements UseCaseContract
{
    public function __construct(
        private AuthService $authService,
    )
    {
    }

    /**
     * Deactivate a product based on product id
     *
     * @param array{id: int}|null $data
     * @param array|null $opts
     * @return ProductDto
     * @throws ProductNotFoundException
     */
    public function run(array $data = null, ?array $opts = null): ProductDto
    {
        $productId = $data['id'];

        return $this->deactivateProduct($productId);

    }

    /**
     * Deactivates a product
     *
     * @param int $id
     * @return ProductDto
     * @throws ProductNotFoundException
     * @throws Exception
     */
    private function deactivateProduct(int $id): ProductDto
    {
        $userId = $this->authService->id();

        if ($userId == null) {
            throw new Exception('invalid_user');
        }

        try {
            $product = Product::findById(
                $id,
            );

            $product->update([
                'active' => false
            ]);

            $product->refresh();

            return ProductDto::fromModel($product);
        } catch (ModelNotFoundException $e) {
            throw new ProductNotFoundException();
        }
    }
}
