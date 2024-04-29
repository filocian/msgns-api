<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO;
use App\Models\User;
use Illuminate\Support\Collection;

final class UserDto extends DTO
{
	public function __construct(
		public int $id,
		public string $name,
		public string $email,
		public string|null $google_id,
		public string $created_at,
		public string $updated_at,
	) {}

	//	public function getGoogleId()
	//	{
	//		return $this->google_id;
	//	}
}
