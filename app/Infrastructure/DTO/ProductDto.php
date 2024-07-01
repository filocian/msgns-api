<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class ProductDto extends BaseDTO
{
	public int $id;
	public string $name;
	public ProductConfigDto $config;
	public ProductTypeDTO $type;
	public UserDto|null $user;
	public bool $active;
	public Carbon $created_at;
	public Carbon $updated_at;

	public function __construct(Model $model)
	{
		$this->id = $model->id;
		$this->name = $model->name;
//		$this->config = new ProductConfigDto($model->config);
		$this->type = ProductTypeDto::fromModel($model->productType);
		$this->user = $model->user ? UserDto::fromModel($model->user) : null;
		$this->active = $model->active;
		$this->created_at = $model->created_at;
		$this->updated_at = $model->updated_at ?? Carbon::create(null);
	}
}
