<?php

declare(strict_types=1);

namespace App\UseCases\Users\Listing;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\User\UserService;
use Exception;

final readonly class UserListUC implements UseCaseContract
{
	public function __construct(
		private UserService $userService
	) {}

	/**
	 * UseCase: Retrieves all products
	 *
	 * @param array|null $data
	 * @param array{perPage:int}|null $opts
	 * @return UserDto[]
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = []): PaginatorDto
	{
		//		dd($this->resolveOptions($opts));
		return $this->userService->findUsers($this->resolveOptions($opts));
	}

	private function resolveOptions(?array $options): array
	{
		return array_merge([
			'page' => 1,
			'perPage' => (int) env('DEFAULT_PAGINATION_LENGTH', 15),
		], $options);
	}
}
