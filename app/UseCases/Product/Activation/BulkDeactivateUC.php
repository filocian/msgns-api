<?php

declare(strict_types=1);

namespace App\UseCases\Product\Activation;

use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;
use Exception;

final readonly class BulkDeactivateUC implements UseCaseContract
{
	public function __construct() {}

	/**
	 * UseCase: Bulk deactivation of a given productId list
	 *
	 * @param mixed $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productsId = $data['productsId'];

		return $this->bulkDeactivateProduct($productsId);
	}

	/**
	 * Deactivates a product
	 *
	 * @param int[] $productsId
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws Exception
	 */
	private function bulkDeactivateProduct(array $productsId): ProductDto
	{
		$productsCollection = Product::whereIn('id', $productsId);

		if ($productsCollection->isEmpty()) {
			throw new ProductNotFoundException();
		}

		$productsCollection->update([
			'active' => false,
		]);

		$productsCollection->refresh();

		return $productsCollection->map(function ($product) {
			ProductDto::fromModel($product);
		});
	}
}
