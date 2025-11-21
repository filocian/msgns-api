<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\Auth\AuthService;

final readonly class StartImpersonateUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService
	) {}

	/**
	 * @param array $data : {user_id: string}
	 * @param array|null $opts
	 * @return UserDto | null
	 */
	public function run(mixed $data = null, ?array $opts = null): UserDto|null
	{
		$userId = (int) $data['user_id'];

		return $this->authService->startImpersonation($userId);
	}
}
