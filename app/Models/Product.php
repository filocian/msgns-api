<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

final class Product extends Model
{
	use HasFactory;

	protected $table = 'products';
	protected $fillable = [
		'product_type_id',
		'user_id',
		'model',
		'linked_to_product_id',
		'target_url',
		'password',
		'name',
		'description',
		'active',
	];
	protected $casts = [
		'active' => 'bool',
	];

	public function productType()
	{
		return $this->belongsTo(ProductType::class, 'product_type_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	public function metadata()
	{
		return $this->hasMany(ProductMetadata::class);
	}

	public function business()
	{
		return $this->hasOne(ProductBusiness::class, 'product_id');
	}

	/**
	 * Get specific metadata by key[].
	 *
	 * @param array $keys
	 * @return Collection
	 */
	public function getMetadataByKeys(array $keys): Collection
	{
		return $this->metadata()->whereIn('key', $keys)->get();
	}

	public function usageStats()
	{
		return $this->hasMany(ProductUsageStats::class);
	}

	/**
	 * Retrieve usage stats for products within a specific datetime range.
	 *
	 * @param string $startDate
	 * @param string $endDate
	 * @return Collection
	 */
	public function getUsageStatsByDateRange($startDate, $endDate)
	{
		return $this->usageStats()
			->whereBetween('date_of_access', [$startDate, $endDate])
			->get();
	}

	/**
	 * Retrieve all products owned by a given user id.
	 *
	 * @param int $userId
	 * @param array{perPage:int}|null $options
	 * @return LengthAwarePaginator|Collection
	 */
	public static function findProductsByUserId(int $userId, ?array $options = []): Collection|LengthAwarePaginator
	{
		$perPage = $options['perPage'] ?? 0;

		if ($perPage === 0) {
			return self::where('user_id', $userId)->get();
		}

		return self::where('user_id', $userId)->paginate($perPage);
	}

	public static function findProducts(?array $options = []): LengthAwarePaginator
	{
		$perPage = $options['perPage'];
		$currentPage = $options['currentPage'] ?? 1;

//		Paginator::currentPageResolver(function () use ($currentPage) {
//			return $currentPage;
//		});

		$filters = [
			'id' => $options['id'] ?? null,
			'code' => $options['code'] ?? null,
			'name' => $options['name'] ?? null,
			'owner_id' => $options['owner_id'] ?? null,
			'owner_email' => $options['owner_email'] ?? null,
			'active' => $options['active'] ?? null,
		];

		$query = Product::query();

		if($filters['id']){
			$query->where('id', $filters['id']);
		}

		if($filters['code']){
			$productType = $filters['code'];
			$query->whereHas('productType', function ($q) use ($productType) {
				$q->where('code', $productType);
			});
		}

		if($filters['name']){
			$query->where('name', $filters['name']);
		}

		if($filters['owner_id']){
			$query->where('user_id', $filters['owner_id']);
		}

		if($filters['owner_email']){
			$userEmail = $filters['owner_email'];
			$query->whereHas('user', function ($q) use ($userEmail) {
				$q->where('email', $userEmail);
			});
		}

		if($filters['active']){
			$query->where('active', $filters['active']);
		}

//		return self::paginate($perPage);
		return $query->paginate($perPage);
	}

	/**
	 * Retrieve all products of given type.
	 *
	 * @param int $productTypeId
	 * @return Collection
	 */
	public static function findByProductType(int $productTypeId): Collection
	{
		return self::where('product_type_id', $productTypeId)->get();
	}

	/**
	 * Retrieve a product with given id.
	 *
	 * @param int $productId
	 * @return Product
	 */
	public static function findById(int $productId): self
	{
		return self::findOrFail($productId);
	}

	/**
	 * Retrieve a product with given id and configuration json pair: key -> value.
	 *
	 * @param int $productId
	 * @param string $configKey
	 * @param string $configValue
	 * @return Product
	 */
	public static function findByConfigPair(int $productId, string $configKey, string $configValue): self
	{
		return self::where('id', $productId)
			->where($configKey, $configValue)
			->firstOrFail();
	}

	public function isPrimaryModel(): bool
	{
		return $this->productType->primary_model == $this->model;
	}

	/**
	 * Get the product that this product is linked to.
	 */
	public function parentProduct()
	{
		return $this->belongsTo(Product::class, 'linked_to_product_id');
	}

	/**
	 * Get the child product that links to this product.
	 */
	public function childProduct()
	{
		return $this->hasOne(Product::class, 'linked_to_product_id');
	}

	/**
	 * Set the child product that links to this product.
	 */
//	public function setChildProduct(int $childId): self
//	{
//		$childProduct = self::findById($childId);
//		$childProduct->linked_to_product_id = $this->id;
//
//		$childProduct->save();
//		$childProduct->refresh();
//
//		return $this;
//	}

	/**
	 * Set the parent product that this product is linked to.
	 */
	public function setParentProduct(int $parentId): self
	{
		$this->linked_to_product_id = $parentId;
		$this->save();
		$this->refresh();

		return $this;
	}

	/**
	 * Retrieve parent candidates for this product.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getParentCandidates()
	{
		return Product::whereHas('productType', function ($query) {
			$query->where('code', $this->productType->code)
				->where('primary_model', $this->productType->primary_model);
		})
			->whereNotNull('user_id')
			->where('user_id', $this->user_id)
			->whereDoesntHave('childProduct')
			->where('id', '!=', $this->id)
			->orderBy('updated_at', 'desc')
			->limit(100)
			->get();
	}

	/**
	 * Retrieve child candidates for this product.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getChildCandidates()
	{
		return Product::whereHas('productType', function ($query) {
			$query->where('code', $this->productType->code)
				->where('secondary_model', $this->productType->secondary_model);
		})
			->whereNotNull('user_id')
			->where('user_id', $this->user_id)
			->whereDoesntHave('parentProduct')
			->where('id', '!=', $this->id)
			->orderBy('updated_at', 'desc')
			->limit(100)
			->get();
	}
}
