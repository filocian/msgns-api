<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Persistence;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Src\Identity\Application\Resources\AdminUserListResource;
use Src\Identity\Domain\Entities\IdentityUser;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\ValueObjects\RbacCatalog;
use Src\Shared\Core\Bus\PaginatedResult;

final class EloquentIdentityUserRepository implements IdentityUserRepository
{
    public function findById(int $id): ?IdentityUser
    {
        $model = User::find($id);
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByEmail(string $email): ?IdentityUser
    {
        $model = User::where('email', strtolower(trim($email)))->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByPendingEmail(string $email): ?IdentityUser
    {
        $model = User::where('pending_email', strtolower(trim($email)))->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByGoogleId(string $googleId): ?IdentityUser
    {
        $model = User::where('google_id', $googleId)->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(IdentityUser $user): IdentityUser
    {
        if ($user->id === 0) {
            // Create new
            $model = User::create([
                'email'                   => strtolower(trim($user->email)),
                'name'                    => $user->name,
                'password'                => $user->hashedPassword,
                'google_id'               => $user->googleId ?? '',
                'active'                  => $user->active,
                'phone'                   => $user->phone,
                'country'                 => $user->country,
                'password_reset_required' => $user->passwordResetRequired,
                'email_verified_at'       => $user->emailVerifiedAt,
                'default_locale'          => $user->defaultLocale ?? 'en_UK',
                'user_agent'              => $user->userAgent,
                'pending_email'           => $user->pendingEmail,
                'last_access'             => now(),
            ]);
            return $this->toDomainEntity($model);
        }

        // Update existing
        $model = User::findOrFail($user->id);
        $model->forceFill([
            'name'                    => $user->name,
            'email'                   => strtolower(trim($user->email)),
            'google_id'               => $user->googleId ?? $model->google_id,
            'active'                  => $user->active,
            'phone'                   => $user->phone,
            'country'                 => $user->country,
            'default_locale'          => $user->defaultLocale,
            'password_reset_required' => $user->passwordResetRequired,
            'email_verified_at'       => $user->emailVerifiedAt,
            'password'                => $user->hashedPassword ?? $model->getAuthPassword(),
            'pending_email'           => $user->pendingEmail,
        ])->save();
        $model->refresh();
        return $this->toDomainEntity($model);
    }

    public function applySignUpSideEffects(int $userId, ?string $userAgent): void
    {
        $user = User::findOrFail($userId);

        $this->ensureDefaultRole($user);

        $updates = [
            'last_access' => CarbonImmutable::now(),
        ];

        if ($userAgent !== null && trim($userAgent) !== '') {
            $updates['user_agent'] = $userAgent;
        }

        $user->update($updates);
    }

    public function applyLoginSideEffects(int $userId, ?string $userAgent): void
    {
        $user = User::findOrFail($userId);

        $this->ensureDefaultRole($user);

        $updates = [
            'last_access' => CarbonImmutable::now(),
        ];

        if (($user->user_agent === null || trim((string) $user->user_agent) === '') && $userAgent !== null && trim($userAgent) !== '') {
            $updates['user_agent'] = $userAgent;
        }

        $user->update($updates);
    }

    public function list(array $filters): PaginatedResult
    {
        $page    = $filters['page'] ?? 1;
        $perPage = $filters['perPage'] ?? 15;
        $sortBy  = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        $search  = $filters['search'] ?? null;
        $active  = $filters['active'] ?? null;
        $role    = $filters['role'] ?? null;

        $query = User::query();

        if ($search !== null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($active !== null) {
            $query->where('active', $active);
        }

        if ($role !== null) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        $query->orderBy($sortBy, $sortDir);

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $items = array_map(
            fn(User $model) => new AdminUserListResource(
                id: $model->id,
                email: $model->email,
                name: $model->name,
                active: (bool) $model->active,
                emailVerified: $model->email_verified_at !== null,
                createdAt: $model->created_at?->toIso8601String() ?? '',
            ),
            $paginated->items()
        );

        return new PaginatedResult(
            items: $items,
            currentPage: $paginated->currentPage(),
            perPage: $paginated->perPage(),
            total: $paginated->total(),
            lastPage: $paginated->lastPage(),
        );
    }

    /**
     * @param array{
     *     search?: string|null,
     *     active?: bool|null,
     *     role?: string|null,
     *     created_from?: string|null,
     *     created_to?: string|null,
     * } $filters
     * @return iterable<int, User>
     */
    public function export(array $filters): iterable
    {
        $search      = $filters['search'] ?? null;
        $active      = $filters['active'] ?? null;
        $role        = $filters['role'] ?? null;
        $createdFrom = $filters['created_from'] ?? null;
        $createdTo   = $filters['created_to'] ?? null;

        $query = User::query()->with('roles');

        if ($search !== null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($active !== null) {
            $query->where('active', $active);
        }

        if ($role !== null) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($createdFrom !== null) {
            $query->where('created_at', '>=', $createdFrom . ' 00:00:00');
        }

        if ($createdTo !== null) {
            $query->where('created_at', '<=', $createdTo . ' 23:59:59');
        }

        $query->orderBy('created_at', 'desc');

        return $query->cursor();
    }

    /**
     * Execute the given callable inside a database transaction.
     *
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function inTransaction(callable $fn): mixed
    {
        return DB::transaction(static function (Connection $_connection) use ($fn): mixed {
            return $fn();
        });
    }

    private function toDomainEntity(User $model): IdentityUser
    {
        return IdentityUser::fromPersistence(
            id: $model->id,
            email: $model->email,
            name: $model->name,
            hashedPassword: $model->getAttributes()['password'] ?? null,
            active: (bool) $model->active,
            emailVerifiedAt: $model->email_verified_at?->toImmutable(),
            googleId: $model->google_id ?: null,
            phone: $model->getAttribute('phone'),
            country: $model->getAttribute('country'),
            passwordResetRequired: (bool) $model->password_reset_required,
            createdAt: $model->created_at?->toImmutable() ?? new \DateTimeImmutable(),
            updatedAt: $model->updated_at?->toImmutable() ?? new \DateTimeImmutable(),
            defaultLocale: $model->getAttribute('default_locale'),
            userAgent: $model->getAttribute('user_agent'),
            pendingEmail: $model->getAttribute('pending_email'),
        );
    }

    private function ensureDefaultRole(User $user): void
    {
        $role = Role::findOrCreate(RbacCatalog::defaultRole(), 'stateful-api');

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
