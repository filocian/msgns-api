<?php

namespace App\Infrastructure\DTO\Whatsapp;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use App\Infrastructure\DTO\ProductDto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class WhatsappPhoneDto extends BaseDTO{
	public int $id;
	public int $product_id;
	public string $phone;
	public string $prefix;
	public Carbon $created_at;
	public Carbon $updated_at;

	public function __construct(Model $model)
	{
		$this->id = $model->id;
		$this->product_id = $model->product_id;
		$this->phone = $model->phone;
		$this->prefix = $model->prefix;
		$this->created_at = $model->created_at;
		$this->updated_at = $model->updated_at;
	}
}