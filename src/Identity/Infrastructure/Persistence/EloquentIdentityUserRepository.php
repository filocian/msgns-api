<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Persistence;

use App\Models\User;
use Src\Identity\Domain\Entities\IdentityUser;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Application\Resources\AdminUserListResource;
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
                'default_locale'          => 'en',
                'last_access'             => now(),
            ]);
            return $this->toDomainEntity($model);
        }

        // Update existing
        $model = User::findOrFail($user->id);
        $model->update([
            'name'                    => $user->name,
            'google_id'               => $user->googleId ?? $model->google_id,
            'active'                  => $user->active,
            'phone'                   => $user->phone,
            'country'                 => $user->country,
            'password_reset_required' => $user->passwordResetRequired,
            'email_verified_at'       => $user->emailVerifiedAt,
            'password'                => $user->hashedPassword ?? $model->getAuthPassword(),
        ]);
        $model->refresh();
        return $this->toDomainEntity($model);
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
        );
    }
}
