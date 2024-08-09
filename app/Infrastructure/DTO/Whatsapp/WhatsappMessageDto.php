<?php

namespace App\Infrastructure\DTO\Whatsapp;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use App\Infrastructure\DTO\ProductDto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class WhatsappMessageDto extends BaseDTO{
	public int $id;
	public int $product_id;
	public WhatsappPhoneDto $phone;
	public WhatsappLocaleDto $locale;
	public string $message;
	public bool $default;
	public Carbon $created_at;
	public Carbon $updated_at;

	public function __construct(Model $model)
	{
		$this->id = $model->id;
		$this->product_id = $model->product_id;
		$this->phone = WhatsappPhoneDto::fromModel($model->phone);
		$this->locale = WhatsappLocaleDto::fromModel($model->locale);
		$this->message = $model->message;
		$this->default = $model->default ?? false;
		$this->created_at = $model->created_at;
		$this->updated_at = $model->updated_at;
	}
}