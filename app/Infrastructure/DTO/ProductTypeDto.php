<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class ProductTypeDto extends BaseDTO
{
	public int $id;
	public string $code;
	public string $name;
	public string $description;
	public string $image_ref;
	public string $primary_model;
	public string $secondary_model;

	public Carbon $created_at;
	public Carbon $updated_at;

	public function __construct(Model $model)
	{
		$this->id = $model->id;
		$this->code = $model->code;
		$this->name = $model->name;
		$this->description = $model->description;
		$this->image_ref = $model->image_ref;
		$this->primary_model = $model->primary_model;
		$this->secondary_model = $model->secondary_model ?? null;
		$this->created_at = $model->created_at;
		$this->updated_at = $model->updated_at;
	}
}
