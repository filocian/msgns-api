<?php

declare(strict_types=1);

namespace App\UseCases\Users\Listing;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\User\UserService;
use Exception;
use Illuminate\Support\Collection;

final readonly class UserListExportUC implements UseCaseContract
{
	public function __construct(
		private UserService $userService
	) {}

	/**
	 * UseCase: Retrieves all products
	 *
	 * @param array|null $data
	 * @param array{perPage:int}|null $opts
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = []): Collection
	{
		return $this->userService->exportUsers($opts);
	}
}
