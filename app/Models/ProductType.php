<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final class ProductType extends Model
{
	protected $table = 'product_types';
	protected $casts = [
		'config_template' => 'array',
	];

	public static function findById(int $productId): self
	{
		return self::findOrFail($productId);
	}

	public static function findByMultipleIds(array $productTypesId): Collection
	{
		return self::whereIn('id', $productTypesId)->get();
	}
}
