<?php

namespace App\Infrastructure\Services\User;

use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\UserDto;
use App\Models\User;

class UserService {

	public function findUsers($opts = []): PaginatorDto {
		$paginatedUsers = User::findUsers($opts);
		return PaginatorDto::fromPaginator($paginatedUsers, UserDto::class);
	}
}
