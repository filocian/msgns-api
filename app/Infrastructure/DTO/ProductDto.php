<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO;

final class ProductDto extends DTO
{
	public function __construct(
		public int                 $id,
		public array               $config,
		public ProductTypeDto|null $type,
		public UserDto|null        $user,
		public bool                $active,
	)
	{
	}
}
