<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class ProductDto extends BaseDTO
{
	public int $id;
	public ProductTypeDTO $type;
	public UserDto|null $user;
	public string $model;
	public string $password;
	public string $target_url;
	public bool $is_primary_model;

	public Product|null $parent;
	public Product|null $child;

	public int $usage;
	public string $name;
	public string $description;
	public bool $active;
	public Carbon $created_at;
	public Carbon $updated_at;

	public function __construct(Model $model)
	{
		$this->id = $model->id;
		$this->type = ProductTypeDto::fromModel($model->productType);
		$this->user = $model->user ? UserDto::fromModel($model->user) : null;
		$this->model = $model->model;
		$this->password = $model->password;
		$this->target_url = $model->target_url;
		$this->is_primary_model = $model->isPrimaryModel();

		$this->parent = $model->parentProduct;
		$this->child = $model->childProduct;

		$this->usage = $model->usage;
		$this->name = $model->name;
		$this->description = $model->description;
		$this->active = $model->active;
		$this->created_at = $model->created_at;
		$this->updated_at = $model->updated_at ?? Carbon::create(null);
	}
}
