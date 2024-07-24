<?php

declare(strict_types=1);

namespace App\Models;

use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\UserDto;
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
	protected $fillable = ['name', 'password_reset_required', 'email', 'password', 'google_id', 'active', 'default_locale', 'user_agent'];

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

	public static function findById(string $id): self|null
	{
		return self::where('id', $id)->firstOrfail();
	}

	public static function findUsers(?array $options = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
	{
		$perPage = $options['perPage'] ?? env('DEFAULT_PAGINATION_LENGTH', 15);
		$page = $options['page'] ?? 1;

		$query = self::customUserQuery($options);

		return $query->orderBy('id', 'asc')->paginate($perPage);
	}

	public static function exportUsers(?array $options = []): Collection
	{
		$query = self::customUserQuery($options);

		return $query->get();
	}

	public static function customUserQuery(array $currentFilters): \Illuminate\Database\Eloquent\Builder
	{
		$filters = [
			'id' => $currentFilters['id'] ?? null,
			'name' => $currentFilters['name'] ?? null,
			'email' => $currentFilters['email'] ?? null,
			'active' => $currentFilters['active'] ?? null,
		];

		$query = User::query();

		if($filters['name']){
			$query->where('name', 'like', '%' . $filters['name'] . '%');
		}

		if($filters['email']){
			$query->where('email', 'like', '%' . $filters['email'] . '%');
		}

		if($filters['id']){
			$query->where('id', $filters['id']);
		}

		return $query;
	}

	public function getRoles(int $userId): mixed
	{
		$user = self::where('id', $userId)->firstOrFail();
		return $user->getRoleNames();
	}
}
