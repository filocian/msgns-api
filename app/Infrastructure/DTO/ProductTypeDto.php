<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO;

final class ProductTypeDto extends DTO
{
	public function __construct(
		public int    $id,
		public string $code,
		public string $name,
		public string $description,
		public array  $config_template,
	)
	{
	}
}
