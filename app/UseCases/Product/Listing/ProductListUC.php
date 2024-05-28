<?php

namespace App\UseCases\Product\Listing;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Product\ProductService;

final class ProductListUC implements UseCaseContract
{
	public function __construct(
		private ProductService $productService
	) {}

	/**
	 * UseCase: Retrieves all products
	 *
	 * @param array|null $data
	 * @param array{perPage:int}|null $opts
	 * @return Array<ProductDto>
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = []): PaginatorDto
	{
		return $this->productService->getProducts($opts);
	}
}