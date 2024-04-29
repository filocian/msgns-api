<?php

declare(strict_types=1);

namespace App\UseCases\Product\Filtering;

use App\DTO\ProductDto;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\Product;
use Exception;

final readonly class FindByOwnerUC implements UseCaseContract
{
	public function __construct() {}

	/**
	 * Retrieves a product list by its owner ID
	 *
	 * @param mixed $data
	 * @param array|null $opts
	 * @return array
	 * @throws ProductNotFoundException
	 */
	public function run(mixed $data = null, array $opts = null): array
	{
		$userId = $data['id'];
		return $this->findByOwner($userId, $opts);
	}

	/**
	 * Retrieves a product list by its owner ID.
	 *
	 * @param int $id
	 * @param array|null $opts
	 * @return ProductDto[]
	 * @throws ProductNotFoundException
	 * @throws Exception
	 */
	private function findByOwner(int $id, ?array $opts = null): array
	{
		if (!isset($id)) {
			throw new Exception('invalid_nfc_id');
		}

		$productsCollection = Product::findProductsByUserId($id);

		if ($productsCollection->isEmpty()) {
			throw new ProductNotFoundException();
		}

		return ProductDto::fromModelCollection($productsCollection);
	}
}
