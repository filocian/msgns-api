<?php

declare(strict_types=1);

namespace App\UseCases\Product\Whatsapp;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\Whatsapp\WhatsappPhoneDto;
use App\Models\Whatsapp\WhatsappPhone;

final readonly class AddPhoneUC implements UseCaseContract
{
	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{id: int, phone_prefix: string, phone_number: string}|null $data
	 * @param array|null $opts
	 * @return WhatsappPhoneDto
	 */
	public function run(mixed $data = null, ?array $opts = null): WhatsappPhoneDto
	{
		$productId = $data['id'];
		$phone = $data['phone_number'];
		$prefix = $data['phone_prefix'];

		$savedMessage = WhatsappPhone::create([
			'product_id' => $productId,
			'phone' => $phone,
			'prefix' => $prefix,
		]);

		return WhatsappPhoneDto::fromModel($savedMessage);
	}
}
