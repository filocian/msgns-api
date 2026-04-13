<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string|null $user_agent
 */
final class User extends Authenticatable implements MustVerifyEmail
{
	use Billable, HasRoles, HasApiTokens, HasFactory, Notifiable;

	protected string $guard_name = 'stateful-api';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'name',
		'password_reset_required',
		'email',
		'password',
		'phone',
		'country',
		'google_id',
		'active',
		'default_locale',
		'user_agent',
		'last_access',
	];

	protected $hidden = ['password', 'remember_token', ];

	protected $casts = [
		'email_verified_at' => 'datetime',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'last_access' => 'datetime',
		'password_reset_required' => 'boolean',
		'password' => 'hashed',
		'active' => 'boolean',
	];

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
	 * @return Collection
	 */
	public function getMetadataByKeys(array $keys): Collection
	{
		return $this->metadata()->whereIn('key', $keys)->get();
	}

	public static function findByGoogleId(string $googleId, string $email = null): self|null
	{
		$user = self::query()
			->when(!empty($googleId), function ($query) use ($googleId) {
				return $query->where('google_id', $googleId);
			})
			->when(!empty($email), function ($query) use ($email) {
				if ($query->getQuery()->wheres) {
					return $query->orWhere('email', $email);
				}
				return $query->where('email', $email);
			})
			->first();

		if (!$user) {
			return null;
		}

		if (!isset($user->google_id)) {
			$user->update([
				'google_id' => $googleId,
			]);

			$user->refresh();
		}

		return $user;
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

		return $query->paginate($perPage);
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
			'timezone' => $currentFilters['timezone'] ?? null,
			'last_access_from' => $currentFilters['last_access_from'] ?? null,
			'last_access_to' => $currentFilters['last_access_to'] ?? null,
			'created_at_from' => $currentFilters['created_at_from'] ?? null,
			'created_at_to' => $currentFilters['created_at_to'] ?? null,
			'order_by' => $currentFilters['order_by'] ?? null,
			'order_direction' => $currentFilters['order_direction'] ?? null,
		];

		$timezone = $filters['timezone'];

		$query = self::query();

		if ($filters['name']) {
			$query->where('name', 'like', '%' . $filters['name'] . '%');
		}

		if ($filters['email']) {
			$query->where('email', 'like', '%' . $filters['email'] . '%');
		}

		if ($filters['id']) {
			$query->where('id', $filters['id']);
		}

		if ($filters['created_at_from'] && $filters['created_at_to']) {
			$from = $filters['created_at_from'];
			$to = $filters['created_at_to'];

			if ($timezone) {
				$carbonFrom = Carbon::createFromFormat('Y-m-d H:i:s', $from, $timezone);
				$carbonTo = Carbon::createFromFormat('Y-m-d H:i:s', $to, $timezone);
				$from = $carbonFrom->setTimezone('UTC')->toDateTimeString();
				$to = $carbonTo->setTimezone('UTC')->toDateTimeString();
			}


			$query->whereBetween('created_at', [$from, $to]);
		}

		if ($filters['last_access_from'] && $filters['last_access_to']) {
			$from = $filters['last_access_from'];
			$to = $filters['last_access_to'];

			if ($timezone) {
				$carbonFrom = Carbon::createFromFormat('Y-m-d H:i:s', $from, $timezone);
				$carbonTo = Carbon::createFromFormat('Y-m-d H:i:s', $to, $timezone);
				$from = $carbonFrom->setTimezone('UTC')->toDateTimeString();
				$to = $carbonTo->setTimezone('UTC')->toDateTimeString();
			}


			$query->whereBetween('last_access', [$from, $to]);
		}

		if ($filters['order_by'] && $filters['order_direction']) {
			$query->orderBy($filters['order_by'], $filters['order_direction']);
		} else {
			$query->orderBy('created_at', 'desc');
		}

		return $query;
	}

	public function getRoles(int $userId): mixed
	{
		$user = self::where('id', $userId)->firstOrFail();
		return $user->getRoleNames();
	}

	public function ghlContact()
	{
		return $this->hasOne(GHLContact::class, 'user_id', 'id');
	}

	public function googleBusinessConnection(): \Illuminate\Database\Eloquent\Relations\HasOne
	{
		return $this->hasOne(\Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection::class, 'user_id', 'id');
	}

	public function instagramConnection(): \Illuminate\Database\Eloquent\Relations\HasOne
	{
		return $this->hasOne(\Src\Instagram\Domain\Models\UserInstagramConnection::class, 'user_id', 'id');
	}
}
