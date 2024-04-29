<?php

declare(strict_types=1);

namespace App\Infrastructure\Contracts;

use App\Infrastructure\DTO\UserDto;

interface SocialLoginServiceContract
{
	public function login(mixed $data): UserDto|null;

	public function signup(mixed $data): UserDto|null;
}
