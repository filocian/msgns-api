<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use Illuminate\Database\Eloquent\Model;

final class GroupedProductDto extends BaseDTO
{
	public int $id;
	public ProductTypeDTO $type;
	public UserDto|null $user;
	public string $model;
	public string $password;
	public string $target_url;
	public ProductBusinessDto|null $business_data;
	public int $usage;
	public string $name;
	public string $description;
	public bool $active;

	public function __construct(Model $model)
	{
		$this->id = $model->id;
		$this->type = ProductTypeDto::fromModel($model->productType);
		$this->user = $model->user ? UserDto::fromModel($model->user) : null;
		$this->model = $model->model;
		$this->password = $model->password;
		$this->target_url = $model->target_url;
		$this->business_data = $model->business ? ProductBusinessDto::fromModel($model->business) : null;
		$this->usage = $model->usage;
		$this->name = $model->name;
		$this->description = $model->description;
		$this->active = $model->active;
	}
}
