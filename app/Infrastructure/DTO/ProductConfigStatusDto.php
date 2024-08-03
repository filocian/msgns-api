<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use Illuminate\Database\Eloquent\Model;

final class ProductConfigStatusDto extends BaseDTO
{
	public string $status;
	public string $label;
	public string $description;

	public function __construct(Model $data)
	{
		$this->status = $data['status_code'];
		$this->label = $data['label'];
		$this->description = $data['description'];
	}
}
