<?php

namespace App\UseCases\Product\Grouping;


use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\CollectionDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;

final class GetParentCandidatesUC implements UseCaseContract{
	public function __construct(
		private ProductService $productService
	) {}

	/**
	 * UseCase: Link product to parent product
	 *
	 * @param array|null $data
	 * @param array|null $opts
	 * @return CollectionDto
	 */
	public function run(mixed $data = null, ?array $opts = []): CollectionDto
	{
		$childId = $data['productId'];

		$child = Product::findById($childId);

		if($child->isPrimaryModel()){
			return CollectionDto::fromModelCollection(collect([]), ProductDto::class);
		}

		$candidates = $child->getParentCandidates();

		return CollectionDto::fromModelCollection($candidates, ProductDto::class);
	}
}