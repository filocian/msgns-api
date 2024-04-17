<?php

namespace App\UseCases\Product\Filtering;

use App\DTO\ProductDto;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\Product;
use App\Services\AuthService;
use Exception;


readonly class FindByCurrentUserUC implements UseCaseContract
{
    public function __construct(
        private AuthService $authService,
    )
    {
    }

    /**
     * UseCase: Retrieves all products for the current user
     *
     * @param array|null $data
     * @param array|null $opts
     * @return ProductDto[]
     * @throws ProductNotFoundException
     */
    public function run(?array $data = null, ?array $opts = null): array
    {
        $userId = $this->authService->id();

        return $this->findByCurrentUser($userId);
    }

    /**
     * @param int|null $userId
     * @param array|null $opts
     * @return ProductDto[]
     * @throws ProductNotFoundException
     * @throws Exception
     */
    private function findByCurrentUser(?int $userId, ?array $opts = null): array
    {
        if ($userId == null) {
            throw new Exception('invalid_user');
        }

        $productsCollection = Product::findByUserId($userId);

        if ($productsCollection->isEmpty()){
            throw new ProductNotFoundException();
        }

        return $productsCollection->map(function ($product) {
            return ProductDto::fromModel($product);
        })->toArray();
    }
}
