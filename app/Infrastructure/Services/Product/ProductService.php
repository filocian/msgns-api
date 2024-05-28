<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Product;

use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class ProductService
{
	public function __construct() {}

	/**
	 * Get Product by productId
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function getProductById() {}

	/**
	 * Get Product[] by productTypeId
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function getProductsByTypeId() {}

	/**
	 * Get Product[] by product name
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function getProductsByName() {}

	/**
	 * Get Product[] by active state
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function getProductsByActiveState() {}

	/**
	 * Search Product[]
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function searchProducts() {}

	/**
	 * Activate a Product
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function activateProduct() {}

	/**
	 * Bulk Activate Products
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function bulkActivateProduct() {}

	/**
	 * Deactivate a Product
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function deactivateProduct() {}

	/**
	 * Bulk Deactivate Products
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function bulkDeactivateProduct() {}

	/**
	 * Assigns a Product to a User
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function assignProduct() {}

	/**
	 * Bulk Assignment of Products to a User
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function bulkAssignProduct() {}

	/**
	 * Register a bought product for a User
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function registerProduct() {}

	/**
	 * Get User Product[]
	 *
	 * @param int $userId
	 * @param array{perPage:int}|null $options
	 * @return LengthAwarePaginator|Collection
	 */
	public function getProductsByUserId(int $userId, ?array $options = []): Collection|LengthAwarePaginator
	{
		return Product::findProductsByUserId($userId, $options);
	}

	public function getProducts($options = []): PaginatorDto
	{
		$paginatedProducts = Product::findProducts($options);
		return PaginatorDto::fromPaginator($paginatedProducts, ProductDto::class);
//		return $productsCollection->map(function ($product) {
//			return ProductDto::fromModel($product);
//		});
	}

	/**
	 * Get User Product[] by productTypeId
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function getUserProductsByTypeId() {}

	/**
	 * Get User Product[] by product name
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function getUserProductsByName() {}

	/**
	 * Get User Product[] by active state
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function getUserProductsByActiveState() {}

	/**
	 * Search User Product[]
	 *
	 * @param array<Product>|Collection $models
	 * @return array<ProductDto>
	 */
	public function searchUserProducts() {}
}
