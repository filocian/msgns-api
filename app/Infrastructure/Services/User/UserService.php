<?php

namespace App\Infrastructure\Services\User;

use App\Infrastructure\DTO\CollectionDto;
use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\UserDto;
use App\Models\User;
use Illuminate\Support\Collection;

class UserService {

	public function findUsers($opts = []): PaginatorDto {
		$paginatedUsers = User::findUsers($opts);
		return PaginatorDto::fromPaginator($paginatedUsers, UserDto::class);
	}

	public function exportUsers($opts = []): Collection {
		return User::exportUsers($opts);
	}

	public function findById($opts = []): UserDto {
		$user = User::findById($opts['id']);
		return UserDto::fromModel($user);
	}
}
