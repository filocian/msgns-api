<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

final class Product extends Model
{
	use HasFactory;

	protected $table = 'products';

	protected $fillable = [
		'product_type_id',
		'user_id',
		'config',
		'name',
		'description',
		'active',
		'tags',
		'admin_tags',
	];

	protected $casts = [
		'active' => 'bool',
		'config' => 'array',
		'tags' => 'array',
		'admin_tags' => 'array',
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

		return self::paginate($perPage);
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
			->where('config->' . $configKey, $configValue)
			->firstOrFail();
	}
}
