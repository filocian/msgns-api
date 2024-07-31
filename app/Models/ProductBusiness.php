<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBusiness extends Model
{
    use HasFactory;
	protected $table = 'product_business';
	protected $fillable = [
		'product_id',
		'user_id',
		'not_a_business',
		'name',
		'types',
		'place_types',
		'size'
	];
	protected $casts = [
		'types' => 'array',
		'place_types' => 'array',
		'not_a_business' => 'boolean'
	];

	public function product()
	{
		return $this->belongsTo(Product::class, 'product_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
}
