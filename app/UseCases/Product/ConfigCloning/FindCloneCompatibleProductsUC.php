<?php

declare(strict_types=1);

namespace App\UseCases\Product\ConfigCloning;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\CollectionDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\Services\Product\CloneProductService;
use App\Models\Product;

final readonly class FindCloneCompatibleProductsUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService,
		private CloneProductService $cloneService,
	) {}

	/**
	 * UseCase: Finds a list of products compatible for cloning
	 *
	 * @param array{product_id: int} $data
	 * @param array|null $opts
	 * @return CollectionDto|null
	 */
	public function run(mixed $data = null, array $opts = null): CollectionDto|null
	{
		$productId = $data['product_id'];
		$userId = $this->authService->id();
		$productDto = ProductDto::fromModel(Product::findById($productId));
		$userProducts = Product::findProductsByUserId($userId);
		$compatibleProducts = $userProducts->filter(function (Product $product) use ($productDto) {
			return $this->cloneService->isCompatible($productDto, $product);
		});

		if ($compatibleProducts->isNotEmpty()) {
			return CollectionDto::fromModelCollection($compatibleProducts, ProductDto::class);
		}

		return CollectionDto::fromModelCollection($userProducts, ProductDto::class);
	}
}
