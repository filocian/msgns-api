<?php

declare(strict_types=1);

namespace App\Models;

use App\Infrastructure\DTO\ProductBusinessDto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductBusiness extends Model
{
	use HasFactory;
	protected $table = 'product_business';
	protected $fillable = ['product_id', 'user_id', 'not_a_business', 'name', 'types', 'place_types', 'size'];
	protected $casts = [
		'types' => 'array',
		'place_types' => 'array',
		'not_a_business' => 'boolean',
	];

	public function product()
	{
		return $this->belongsTo(Product::class, 'product_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	public static function findByProductId(int $id): ProductBusinessDto|null
	{
		$data = self::query()->find(['product_id' => $id])->first();

		if (!$data) {
			return null;
		}

		return ProductBusinessDto::fromModel($data);
	}
}
