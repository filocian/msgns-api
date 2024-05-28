<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class RoleDto extends BaseDTO
{
	public int $id;
	public string $name;


	public function __construct(Model $model)
	{
		$this->id = $model->id;
		$this->name = $model->name;
	}

}
