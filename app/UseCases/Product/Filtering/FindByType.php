<?php

declare(strict_types=1);

namespace App\UseCases\Product\Filtering;

use App\Exceptions\Product\InvalidProductTypeException;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class FindByType implements UseCaseContract
{
	public function __construct() {}

	/**
	 * Retrieves a product list by its product type
	 *
	 * @param mixed $data
	 * @return ProductDto[]
	 * @throws ProductNotFoundException|InvalidProductTypeException
	 */
	public function run(mixed $data = null, array $opts = null): array
	{
		$typeId = $data['id'];

		return $this->findByType($typeId, $opts);
	}

	/**
	 * Retrieves a product list by its product type
	 *
	 * @param int $typeId
	 * @param array|null $opts
	 * @return ProductDto[]
	 * @throws ProductNotFoundException
	 * @throws InvalidProductTypeException
	 */
	private function findByType(int $typeId, ?array $opts = null): array
	{
		try {
			ProductType::find($typeId)->firstOrFail();
		} catch (ModelNotFoundException $e) {
			throw new InvalidProductTypeException();
		}

		$productsCollection = Product::findProductsByUserId($typeId);

		if ($productsCollection->isEmpty()) {
			throw new ProductNotFoundException();
		}

		return $productsCollection->map(function ($product) {
			ProductDto::fromModel($product);
		});
	}
}
