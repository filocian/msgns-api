<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\Auth\AuthService;

final readonly class SignUpUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService
	) {}

	/**
	 * @param array $data : {email: string, password: string}
	 * @param array|null $opts
	 * @return UserDto
	 */
	public function run(mixed $data = null, ?array $opts = null): UserDto
	{
		return $this->authService->signUp($data);
	}
}
