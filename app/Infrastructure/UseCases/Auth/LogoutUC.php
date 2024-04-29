<?php

declare(strict_types=1);

namespace App\Infrastructure\UseCases\Auth;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\Auth\AuthService;

final readonly class LogoutUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService
	) {}

	public function run(mixed $data = null, ?array $opts = null)
	{
		return $this->authService->logout();
	}
}
