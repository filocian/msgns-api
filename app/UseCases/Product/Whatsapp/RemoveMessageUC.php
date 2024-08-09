<?php

declare(strict_types=1);

namespace App\UseCases\Product\Whatsapp;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\Whatsapp\WhatsappMessageDto;
use App\Models\Whatsapp\WhatsappMessage;

final readonly class RemoveMessageUC implements UseCaseContract
{
	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{message_id: int}|null $data
	 * @param array|null $opts
	 * @return WhatsappMessageDto
	 */
	public function run(mixed $data = null, ?array $opts = null): WhatsappMessageDto
	{
		$messageId = $data['message_id'];

		$message = WhatsappMessage::find($messageId);

		if ($message) {
			$message->delete();
		}

		return WhatsappMessageDto::fromModel($message);
	}
}
