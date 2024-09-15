<?php

declare(strict_types=1);

namespace App\UseCases\Product\Listing;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\Product\ProductService;

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
