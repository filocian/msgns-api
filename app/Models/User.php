<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

final class User extends Authenticatable implements MustVerifyEmail
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
		'password_reset_required' => 'boolean',
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
	public function getMetadataByKeys(array $keys): Collection
	{
		return $this->metadata()->whereIn('key', $keys)->get();
	}

	public static function findByGoogleId(string $googleId): self|null
	{
		return self::where('google_id', $googleId)->first();
	}

	public static function findUsers(?array $options = []): LengthAwarePaginator
	{
		$perPage = $options['perPage'] ?? env('DEFAULT_PAGINATION_LENGTH', 15);
		$page = $options['currentPage'] ?? 1;

		$items = ($perPage === 0)
			? self::all()
			: self::where(null)->skip(($page - 1) * $perPage)->take($perPage)->get();

		return new LengthAwarePaginator(
			$items, // Items for the current page
			self::count(), // Total number of items
			$perPage, // Items per page
			$page, // Current page
		);
	}
}
