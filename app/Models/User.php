<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Traits\HasRoles;

final class User extends Authenticatable
{
	use HasRoles, HasApiTokens, HasFactory, Notifiable;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = ['name', 'email', 'password', 'google_id', 'active', 'default_locale', 'user_agent'];

	protected $hidden = ['password', 'remember_token', ];

	protected $casts = [
		'email_verified_at' => 'datetime',
		'password' => 'hashed',
		'active' => 'boolean',
		'user_agent' => 'array'
	];

//	protected static function boot()
//	{
//		parent::boot();
//
//		self::creating(function ($model) {
//			$model->uuid = (string) Uuid::uuid4();
//		});
//	}

	public function products()
	{
		return $this->hasMany(Product::class);
	}

	public function metadata()
	{
		return $this->hasMany(UserMetadata::class);
	}

	public function businesses()
	{
		return $this->hasMany(ProductBusiness::class, 'user_id');
	}

	/**
	 * Get specific metadata by key[].
	 *
	 * @param array $keys
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getMetadataByKeys(array $keys): \Illuminate\Database\Eloquent\Collection
	{
		return $this->metadata()->whereIn('key', $keys)->get();
	}

	public static function findByGoogleId(string $googleId): self|null
	{
		return self::where('google_id', $googleId)->first();
	}
}
