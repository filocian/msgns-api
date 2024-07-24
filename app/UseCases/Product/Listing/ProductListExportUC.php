<?php

namespace App\UseCases\Product\Listing;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\CollectionDto;
use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\Services\Product\ProductService;
use Illuminate\Database\Eloquent\Collection;

final class ProductListExportUC implements UseCaseContract
{
	public function __construct(
		private ProductService $productService
	) {}

	/**
	 * UseCase: Retrieves all products
	 *
	 * @param array|null $data
	 * @param array{perPage:int}|null $opts
	 * @return \Illuminate\Support\Collection
	 */
	public function run(mixed $data = null, ?array $opts = []): \Illuminate\Support\Collection
	{
		return $this->productService->exportProducts($opts);
	}
}