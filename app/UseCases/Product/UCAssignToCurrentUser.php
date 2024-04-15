<?php

namespace App\UseCases\Product;

use App\DTO\ProductDto;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\Product;
use App\Services\AuthService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class UCAssignToCurrentUser implements UseCaseContract
{
    public function __construct(
        private readonly AuthService $authService,

    )
    {
    }

    /**
     * UseCase: Assign a product to current user and activates it
     *
     * @param array{id: int, password: string}|null $data
     * @param array|null $opts
     * @return ProductDto
     * @throws ProductNotFoundException
     */
    public function run(array $data = null, ?array $opts = null): ProductDto
    {
        $productId = $data['id'];
        $password = $data['password'];

        return $this->assignProductToCurrentUser($productId, $password);
    }

    /**
     * Assign a product to current user and activates it
     *
     * @param int $productId
     * @param string $password
     * @return ProductDto
     * @throws ProductNotFoundException
     * @throws Exception
     */
    private function assignProductToCurrentUser(int $productId, string $password): ProductDto
    {
        $userId = $this->authService->id();

        if ($userId == null) {
            throw new Exception('invalid_user');
        }

        try {
            $product = Product::findByConfigPair(
                $productId,
                'password',
                $password
            );

            $product->update([
                'user_id' => $userId,
                'active' => true
            ]);

            return ProductDto::fromModel($product);
        } catch (ModelNotFoundException $e) {
            throw new ProductNotFoundException();
        }
    }
}
