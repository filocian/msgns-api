<?php

declare(strict_types=1);

namespace App\UseCases\Product\Listing;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\PaginatorDto;
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
	 * @return PaginatorDto
	 */
	public function run(mixed $data = null, ?array $opts = []): PaginatorDto
	{
		return $this->productService->getProducts($this->resolveOptions($opts));
	}

	/**
	 * Resolve UseCase Options
	 *
	 * @param array{perPage:int}|null $options
	 * @return array
	 */
	private function resolveOptions(?array $options): array
	{
		return array_merge([
			'perPage' => (int) env('DEFAULT_PAGINATION_LENGTH', 15),
		], $options);
	}
}
