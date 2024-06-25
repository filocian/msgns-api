<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class UserDto extends BaseDTO
{
	public int $id;
	public string $name;
	public string $email;
	public string|null $google_id;
	public Carbon $created_at;
	public Carbon $updated_at;

	public function __construct(Model $model)
	{
		$this->id = $model->id;
		$this->name = $model->name;
		$this->email = $model->email;
		$this->google_id = $model->google_id;
		$this->created_at = $model->created_at;
		$this->updated_at = $model->updated_at;
	}

	public function getGoogleId()
	{
		return $this->google_id;
	}
}
