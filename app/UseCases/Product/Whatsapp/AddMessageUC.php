<?php

declare(strict_types=1);

namespace App\UseCases\Product\Whatsapp;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\Whatsapp\WhatsappMessageDto;
use App\Models\Whatsapp\WhatsappMessage;

final readonly class AddMessageUC implements UseCaseContract
{
	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{id: int, phone_id: int, message_locale_id: int, message: string, default: bool}|null $data
	 * @param array|null $opts
	 * @return WhatsappMessageDto
	 */
	public function run(mixed $data = null, ?array $opts = null): WhatsappMessageDto
	{
		$productId = $data['id'];
		$phoneId = $data['phone_id'];
		$localeId = $data['message_locale_id'];
		$message = $data['message'];
		$default = $data['default'] ?? false;

		$savedMessage = WhatsappMessage::create([
			'product_id' => $productId,
			'phone_id' => $phoneId,
			'locale_id' => $localeId,
			'message' => $message,
			'default' => $default
		]);

		return WhatsappMessageDto::fromModel($savedMessage);
	}
}
