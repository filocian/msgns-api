<?php

declare(strict_types=1);

namespace App\Models\Fancelet;

use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FanceletContentGallery extends Model
{
	use HasFactory;

	protected $table = 'fancelet_content_gallery';
	protected $fillable = ['product_type_id', ];
	protected $casts = [
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

	public function productType()
	{
		return $this->belongsTo(ProductType::class, 'product_type_id');
	}

	public static function findByProductTypeId(int $productTypeId): self
	{
		return self::where('product_type_id', $productTypeId)->first();
	}
}
