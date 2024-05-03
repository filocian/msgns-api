<?php

declare(strict_types=1);

namespace App\UseCases\Product\Businesses;

use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;
use Exception;

final readonly class BulkAddBusinessUC implements UseCaseContract
{
	public function __construct() {}

	/**
	 * UseCase: Bulk assignation of a given productId list to a given userId
	 *
	 * @param mixed $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productsId = $data['productsId'];
		$userId = $data['userId'];

		return $this->bulkAssignToUser($productsId, $userId);
	}

	/**
	 * Bulk assignation of a given productId list to a given userId
	 *
	 * @param int[] $productsId
	 * @param string $userId
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws Exception
	 */
	private function bulkAssignToUser(array $productsId, string $userId): ProductDto
	{
		$productsCollection = Product::whereIn('id', $productsId);

		if ($productsCollection->isEmpty()) {
			throw new ProductNotFoundException();
		}

		$productsCollection->update([
			'user_id' => $userId,
		]);

		$productsCollection->refresh();

		return $productsCollection->map(function ($product) {
			ProductDto::fromModel($product);
		});
	}
}
