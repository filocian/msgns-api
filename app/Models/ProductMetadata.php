<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductMetadata extends Model
{
	use HasFactory;

	protected $fillable = ['product_id', 'key', 'value'];

	public function product()
	{
		return $this->belongsTo(Product::class);
	}
}
