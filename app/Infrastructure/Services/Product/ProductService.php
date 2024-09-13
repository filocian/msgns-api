<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Product;

use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\DynamoDb\DynamoDbService;
use App\Models\Product;
use App\Models\ProductConfigurationStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class ProductService
{
	public function __construct(private DynamoDbService $dynamoDbService) {}

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
	}

	public function exportProducts($options = []): Collection
	{
		return Product::exportProducts($options);
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

	public function setChildProduct(Product $parent, int $childId)
	{
		$child = Product::findById($childId);

		return $child->setParentProduct($parent->id);
	}

	public function setParentProduct(Product $child, int $parentId)
	{
		$parent = Product::findById($parentId);

		return $child->setParentProduct($parent->id);
	}

	public function getParentCandidates(Product $child)
	{
		return $child->getParentCandidates();
	}

	public function getChildCandidates(Product $parent)
	{
		return $parent->getChildCandidates();
	}

	public function resolveConfigurationStatus(Product $product, string $actionStatus): string
	{
		$productStatus = $product->configuration_status;
		$notStarted = ProductConfigurationStatus::$STATUS_NOT_STARTED;
		$assigned = ProductConfigurationStatus::$STATUS_ASSIGNED;
		$targetSet = ProductConfigurationStatus::$STATUS_TARGET_SET;
		$businessSet = ProductConfigurationStatus::$STATUS_BUSINESS_SET;
		$completed = ProductConfigurationStatus::$STATUS_COMPLETED;

		if ($productStatus === $completed) {
			return $completed;
		}

		if ($productStatus === $notStarted) {
			if ($actionStatus === $assigned) {
				return $assigned;
			}
			return $notStarted;
		}

		if ($productStatus === $assigned) {
			if ($actionStatus === $targetSet) {
				return $targetSet;
			}
			return $assigned;
		}

		if ($productStatus === $targetSet) {
			if ($actionStatus === $businessSet) {
				return $businessSet;
			}
			return $targetSet;
		}

		if ($productStatus === $businessSet) {
			if ($actionStatus === $completed) {
				return $completed;
			}
			return $businessSet;
		}

		return $productStatus;
	}

	public function getProductUsageOverview(int $userId): array
	{
		$products = Product::findProductsByUserId($userId, ['perPage' => 0]);
		$totalUses = 0;
		$totalProducts = 0;

		$productsUsage = $products->map(function ($product) use (&$totalUses, &$totalProducts) {
			$totalUses += $product->usage;
			$totalProducts += 1;
			return [
				'id' => $product->id,
				'name' => $product->name,
				'usage' => $product->usage,
			];
		});

		return [
			'total_usage' => $totalUses,
			'total_products' => $totalProducts,
			'usage_by_product' => $productsUsage,
		];
	}

	public function testDynamoDB()
	{
		$this->dynamoDbService->test();
	}
}
