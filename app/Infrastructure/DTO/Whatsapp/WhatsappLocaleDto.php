<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Whatsapp;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class WhatsappLocaleDto extends BaseDTO
{
	public int $id;
	public string $code;
	public Carbon $created_at;
	public Carbon $updated_at;

	public function __construct(Model $model)
	{
		$this->id = $model->id;
		$this->code = $model->code;
		$this->created_at = $model->created_at;
		$this->updated_at = $model->updated_at;
	}
}
