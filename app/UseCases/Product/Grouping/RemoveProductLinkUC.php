<?php

namespace App\UseCases\Product\Grouping;


use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;

final class RemoveProductLinkUC implements UseCaseContract{
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
		$childId = $data['childId'];

		$child = Product::findById($childId);
		$child->linked_to_product_id = null;
		$child->save();
		$child->refresh();

		return ProductDto::fromModel($child);
	}
}