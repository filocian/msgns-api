<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\User;

final readonly class HasAdminRightsUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService
	) {}

	public function run(mixed $data = null, ?array $opts = null): bool
	{
		$userId = $data['id'];
		return $this->authService->hasAdminRole($userId);
	}
}
