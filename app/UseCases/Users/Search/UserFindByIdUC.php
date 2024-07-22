<?php

namespace App\UseCases\Users\Search;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\User\UserService;
use Exception;

final readonly class UserFindByIdUC implements UseCaseContract
{
	public function __construct(
		private UserService $userService
	) {}

	/**
	 * UseCase: Retrieves all products
	 *
	 * @param array|null $data
	 * @param array{perPage:int}|null $opts
	 * @return UserDto
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = []): UserDto
	{
		return $this->userService->findById(['id' => $data['id']]);
	}
}