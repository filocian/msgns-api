<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserMetadata extends Model
{
	use HasFactory;

	protected $fillable = ['user_id', 'key', 'value'];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
