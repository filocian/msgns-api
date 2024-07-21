<?php

namespace App\UseCases\Product\Grouping;


use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\CollectionDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;

final class GetChildCandidatesUC implements UseCaseContract{
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
		$parentId = $data['productId'];

		$parent = Product::findById($parentId);

		if(!$parent->isPrimaryModel()){
			return CollectionDto::fromModelCollection(collect([]), ProductDto::class);
		}

		$candidates = $parent->getChildCandidates();

		return CollectionDto::fromModelCollection($candidates, ProductDto::class);
	}
}