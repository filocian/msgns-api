<?php

namespace App\UseCases\Product\Grouping;


use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;

final class SetGroupUC implements UseCaseContract{
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
		$referenceId = $data['referenceId'];
		$candidateId = $data['candidateId'];

		$referenceProduct = Product::findById($referenceId);
		$candidateProduct = Product::findById($candidateId);

		if($referenceProduct->isPrimaryModel()){
			$referenceProduct->setChildProduct($candidateProduct->id);
		} else{
			$referenceProduct->setParentProduct($candidateProduct->id);
		}

		return ProductDto::fromModel($referenceProduct);
	}
}