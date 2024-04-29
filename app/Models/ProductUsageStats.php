<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductUsageStats extends Model
{
	use HasFactory;

	protected $fillable = ['product_id', 'date_of_access', 'hour_of_access', 'access_count'];

	public function product()
	{
		return $this->belongsTo(Product::class);
	}
}
