<?php

namespace App\UseCases\Product;

use App\DTO\ProductDto;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Repositories\ProductRepository;
use App\Services\AuthService;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class UCFindProductByLoggedUser implements UseCaseContract
{
    public string $hello = 'fck';

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly AuthService       $authService,

    )
    {
        $this->hello = 'hello';
    }

    /**
     * @param array|null $data
     * @param array|null $opts
     * @return ProductDto
     * @throws ProductNotFoundException
     */
    public function run(?array $data = null, ?array $opts = null): ProductDto
    {
        $userId = $this->authService->id();

        try {
            return $this->productRepository->findOneBy([
                'user_id' => $userId,
            ]);
        } catch (ModelNotFoundException $e) {
            throw new ProductNotFoundException();
        }
    }
}
