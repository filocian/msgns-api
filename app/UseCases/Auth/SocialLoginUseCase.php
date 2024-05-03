<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\UseCases\Auth\RandomException;

final class SocialLoginUseCase implements UseCaseContract
{
	public function __construct(
		private readonly AuthService $authService
	) {}

	/**
	 * @throws RandomException
	 */
	public function run(mixed $data = null, ?array $opts = null)
	{
		$user = $this->authService->socialLogin($data['provider'], $data);

		if (!$user) {
			$user = $this->authService->socialSignup($data['provider'], $data);
			$this->authService->autoLogin($user);
		}

		return $user;
	}
}
