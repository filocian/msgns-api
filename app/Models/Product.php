<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Whatsapp\WhatsappMessage;
use App\Models\Whatsapp\WhatsappPhone;
use Carbon\Carbon;
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
		'configuration_status',
		'assigned_at'
	];
	protected $casts = [
		'active' => 'bool',
		'assigned_at' => 'date'
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

	public function configurationStatus()
	{
		return $this->belongsTo(ProductConfigurationStatus::class, 'configuration_status', 'status_code');
	}

	public function whatsappMessages()
	{
		return $this->hasMany(WhatsappMessage::class);
	}

	public function whatsappPhones()
	{
		return $this->hasMany(WhatsappPhone::class);
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
		$query = self::where('user_id', $userId)
			->where('linked_to_product_id', null);

		if ($perPage === 0) {
			return $query->get();
		}

		return $query->paginate($perPage);
	}

	public static function findProducts(?array $options = []): LengthAwarePaginator
	{
		$perPage = $options['perPage'];

		$query = self::customProductQuery($options);

		return $query->paginate($perPage);
	}

	public static function exportProducts(?array $options = []): Collection
	{
		$query = self::customProductQuery($options);

		return $query->get();
	}

	public static function customProductQuery(array $currentFilters): \Illuminate\Database\Eloquent\Builder
	{
		$filters = [
			'id' => $currentFilters['id'] ?? null,
			'code' => $currentFilters['code'] ?? null,
			'name' => $currentFilters['name'] ?? null,
			'model' => $currentFilters['model'] ?? null,
			'owner_id' => $currentFilters['owner_id'] ?? null,
			'owner_email' => $currentFilters['owner_email'] ?? null,
			'config_status' => $currentFilters['config_status'] ?? null,
			'active' => $currentFilters['active'] ?? null,
			'timezone' => $currentFilters['timezone'] ?? null,
			'assigned_at_from' => $currentFilters['assigned_at_from'] ?? null,
			'assigned_at_to' => $currentFilters['assigned_at_to'] ?? null,
			'order_by' => $currentFilters['order_by'] ?? null,
			'order_direction' => $currentFilters['order_direction'] ?? null,
		];

		$query = Product::query();

		if ($filters['id']) {
			$query->where('id', $filters['id']);
		}

		if ($filters['code']) {
			$productType = $filters['code'];
			$query->whereHas('productType', function ($q) use ($productType) {
				$q->where('code', 'like', '%' . $productType . '%');
			});
		}

		if ($filters['model']) {
			$query->where('model', 'like', '%' . $filters['model'] . '%');
		}

		if ($filters['name']) {
			$query->where('name', 'like', '%' . $filters['name'] . '%');
		}

		if ($filters['owner_id']) {
			$query->where('user_id', $filters['owner_id']);
		}

		if ($filters['owner_email']) {
			$userEmail = $filters['owner_email'];
			$query->whereHas('user', function ($q) use ($userEmail) {
				$q->where('email', 'like', '%' . $userEmail . '%');
			});
		}

		if ($filters['config_status']) {
			$query->where('configuration_status', $filters['config_status']);
		}

		if ($filters['active']) {
			$query->where('active', $filters['active'] == '1' ? 1 : 0);
		}

		if ($filters['assigned_at_from'] && $filters['assigned_at_to']) {
			$timezone = $filters['timezone'];
			$from = $filters['assigned_at_from'];
			$to = $filters['assigned_at_to'];

			if($timezone){
				$carbonFrom = Carbon::createFromFormat('Y-m-d H:i:s', $from, $timezone);
				$carbonTo = Carbon::createFromFormat('Y-m-d H:i:s', $to, $timezone);
				$from = $carbonFrom->setTimezone('UTC')->toDateTimeString();
				$to = $carbonTo->setTimezone('UTC')->toDateTimeString();
			}


			$query->whereBetween('assigned_at', [$from, $to]);
		}

		if($filters['order_by'] && $filters['order_direction']){
			$query->orderBy($filters['order_by'], $filters['order_direction']);
		} else {
			$query->orderBy('assigned_at', 'desc');
		}

		return $query;
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
	public function setChildProduct(int $childId): self
	{
		$childProduct = self::findById($childId);
		$childProduct->linked_to_product_id = $this->id;

		$childProduct->save();
		$childProduct->refresh();

		return $this;
	}

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
	 * Removes product link (child reference).
	 */
	public function removeProductLink(int $childId): self
	{
		$child = self::findById($childId);
		$child->linked_to_product_id = null;
		$child->save();
		$child->refresh();

		return $child;
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
				->where('primary_model', $this->productType->primary_model)
				->whereNotIn('code', ['P-GW-GO-RC', 'P-GM-GO-RC']);
		})
			->whereNotNull('user_id')
			->where('user_id', $this->user_id)
			->where('id', '!=', $this->id)
			->where('model', $this->productType->primary_model)
			->whereDoesntHave('childProduct')
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
				->where('secondary_model', $this->productType->secondary_model)
				->whereNotIn('code', ['P-GW-GO-RC', 'P-GM-GO-RC']);
		})
			->whereNotNull('user_id')
			->where('user_id', $this->user_id)
			->where('id', '!=', $this->id)
			->where('model', $this->productType->secondary_model)
			->whereDoesntHave('parentProduct')
			->orderBy('updated_at', 'desc')
			->limit(100)
			->get();
	}
}
