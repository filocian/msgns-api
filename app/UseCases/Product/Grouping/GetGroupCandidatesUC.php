<?php

declare(strict_types=1);

namespace App\UseCases\Product\Grouping;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\CollectionDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;

final class GetGroupCandidatesUC implements UseCaseContract
{
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
		$referenceProductId = $data['productId'];

		$referenceProduct = Product::findById($referenceProductId);

		if ($referenceProduct->isPrimaryModel()) {
			$candidates = $referenceProduct->getChildCandidates();
		} else {
			$candidates = $referenceProduct->getParentCandidates();
		}

		return CollectionDto::fromModelCollection($candidates, ProductDto::class);
	}
}
