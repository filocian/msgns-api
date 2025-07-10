<?php

declare(strict_types=1);

namespace App\UseCases\Product\Configuration;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\CollectionDto;
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
	public function run(mixed $data = null, array $opts = null): array|null
	{
		$productId = $data['product_id'];
		$userId = $this->authService->id();
		$product = Product::findById($productId);
		$userProducts = Product::findProductsByUserId($userId);
		$compatibleProducts = $userProducts->filter(function (Product $candidate) use ($product) {
			return $this->cloneService->isCompatible($product, $candidate) === true;
		});

		if ($compatibleProducts->isEmpty()) {
			return null;
		}

		$result = [];

		$compatibleProducts->each(function (Product $candidate) use (&$result) {
			$result[] = $candidate;
		});

		return $result;
	}
}
