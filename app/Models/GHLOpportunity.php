<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

final class GHLOpportunity extends Model
{
	use HasRoles, HasApiTokens, HasFactory, Notifiable;

	protected $table = 'ghl_opportunity_data';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = ['product_id', 'opportunity_id', ];

	protected $casts = [
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

	public function product()
	{
		return $this->belongsTo(Product::class);
	}
}
