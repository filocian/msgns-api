<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\User;

use App\Events\User\UserDataUpdatedEvent;
use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\UserDto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class UserService
{
	public function findUsers($opts = []): PaginatorDto
	{
		$paginatedUsers = User::findUsers($opts);
		return PaginatorDto::fromPaginator($paginatedUsers, UserDto::class);
	}

	public function exportUsers($opts = []): Collection
	{
		return User::exportUsers($opts);
	}

	public function findById($opts = []): UserDto
	{
		$user = User::findById((string) $opts['id']);
		return UserDto::fromModel($user);
	}

	public function updateUserAgent(int $userId, string|null $userAgent): void
	{
		$user = User::findById((string) $userId);

		if (!$userAgent || $user->user_agent) {
			return;
		}

		$user->user_agent = $userAgent;
		$user->save();
		$user->refresh();
	}

	public function updateUserLastAccess(int $userId): void
	{
		$user = User::findById((string) $userId);

		$user->last_access = Carbon::now();
		$user->save();
	}

	/**
	 * Update User data: email, name, phone.
	 *
	 * @param array{user_id: int, email: string, name: string, phone: string|null, default_locale: string|null} $data
	 * @return UserDto|null
	 */
	public function updateUserData(array $data): UserDto|null
	{
		$userId = (string) $data['user_id'];
		$email = $data['email'];
		$name = $data['name'];
		$newUserdata = [
			'email' => $email,
			'name' => $name,
		];

		if (isset($data['phone'])) {
			$newUserdata['phone'] = $data['phone'];
		}

		if (isset($data['default_locale'])) {
			$newUserdata['default_locale'] = $this->resolveUserDefaultLocale($data['default_locale']);
		}

		$user = User::findById($userId);

		if ($user->email !== $email && $this->mailExists($email)) {
			return null;
		}

		$user->update($newUserdata);

		event(new UserDataUpdatedEvent($user));

		return UserDto::fromModel($user);
	}

	public function mailExists(string $email): bool
	{
		return User::where('email', $email)->exists();
	}

	public function resolveUserDefaultLocale(string $lang): string
	{
		return match ($lang) {
			'ca' => 'ca_ES',
			'es' => 'es_ES',
			'fr' => 'fr_FR',
			'de' => 'de_DE',
			'it' => 'it_IT',
			default => 'en_UK'
		};
	}
}
