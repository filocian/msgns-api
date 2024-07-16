<?php

namespace App\UseCases\Product\Relationship;


use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;

final class SetParentUC implements UseCaseContract{
	public function __construct(
		private ProductService $productService
	) {}

	/**
	 * UseCase: Link product to parent product
	 *
	 * @param array|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 */
	public function run(mixed $data = null, ?array $opts = []): ProductDto
	{
		$childId = $data['productId'];
		$parentId = $data['parentId'];

		$child = Product::findById($childId);
		$parent = Product::findById($parentId);

		$child->setParentProduct($parent->id);

		return ProductDto::fromModel($child);
	}
}