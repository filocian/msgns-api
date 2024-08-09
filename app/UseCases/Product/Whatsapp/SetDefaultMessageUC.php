<?php

declare(strict_types=1);

namespace App\UseCases\Product\Whatsapp;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\Whatsapp\WhatsappMessageDto;
use App\Models\Whatsapp\WhatsappMessage;

final readonly class SetDefaultMessageUC implements UseCaseContract
{
	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{product_id: int, message_id: int}|null $data
	 * @param array|null $opts
	 * @return WhatsappMessageDto
	 */
	public function run(mixed $data = null, ?array $opts = null): WhatsappMessageDto
	{
		$productId = $data['product_id'];
		$messageId = $data['message_id'];

		WhatsappMessage::where('product_id', $productId)
			->update(['default' => false]);

		$message = WhatsappMessage::find($messageId);

		if ($message) {
			$message->default = true;
			$message->save();
			$message->refresh();
		}

		return WhatsappMessageDto::fromModel($message);
	}
}
