<?php

declare(strict_types=1);

namespace App\UseCases\Product\Whatsapp;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\Whatsapp\WhatsappPhoneDto;
use App\Models\Whatsapp\WhatsappPhone;

final readonly class RemovePhoneUC implements UseCaseContract
{
	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{phone_id: int}|null $data
	 * @param array|null $opts
	 * @return WhatsappPhoneDto
	 */
	public function run(mixed $data = null, ?array $opts = null): WhatsappPhoneDto
	{
		$phoneId = $data['phone_id'];

		$phone = WhatsappPhone::find($phoneId);

		if ($phone) {
			$phone->delete();
		}

		return WhatsappPhoneDto::fromModel($phone);
	}
}
