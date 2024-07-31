<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class ProductBusinessDto extends BaseDTO
{
	public int $id;
	public int $product_id;
	public int $user_id;
	public bool $not_a_business;
	public string|null $name;
	public array $types;
	public array $place_types;
	public string|null $size;
	public Carbon $created_at;
	public Carbon $updated_at;

	public function __construct(Model $model)
	{
		$this->id = $model->id;
		$this->product_id = $model->product_id;
		$this->user_id = $model->user_id;
		$this->not_a_business = $model->not_a_business ?? false;
		$this->name = $model->name;
		$this->types = $model->types;
		$this->place_types = $model->place_types;
		$this->size = $model->size;
		$this->created_at = $model->created_at;
		$this->updated_at = $model->updated_at;
	}
}
