<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;

final class ProductConfigDto extends BaseDTO
{
	public string $target_url;
	public string $image;
	public string $password;
	public string|null $target_url_2;
	public string|null $target_url_3;
	public string|null $image_2;
	public string|null $image_3;

	public function __construct(array $data)
	{
		$this->target_url = $data['target_url'];
		$this->target_url_2 = $data['target_url_2'] ?? null;
		$this->target_url_3 = $data['target_url_3'] ?? null;
		$this->password = $data['password'];
		$this->image = $data['image_path'];
		$this->image_2 = $data['image_path_2'] ?? null;
		$this->image_3 = $data['image_path_3'] ?? null;
	}
}
