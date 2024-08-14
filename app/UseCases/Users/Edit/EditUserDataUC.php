<?php

namespace App\UseCases\Users\Edit;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\User\UserService;
use Exception;

final readonly class EditUserDataUC implements UseCaseContract
{
	public function __construct(
		private UserService $userService
	) {}

	/**
	 * UseCase: Retrieves all products
	 *
	 * @param array|null $data
	 * @param array{user_id: int, email: string, name: string, phone: string|null}|null $opts
	 * @return UserDto|null
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = []): UserDto|null
	{
		return $this->userService->updateUserData($data);
	}
}