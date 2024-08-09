<?php

declare(strict_types=1);

namespace App\UseCases\Product\Whatsapp;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\CollectionDto;
use App\Infrastructure\DTO\Whatsapp\WhatsappMessageDto;
use App\Models\Product;

final readonly class ListMessagesUC implements UseCaseContract
{
	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{id: int}|null $data
	 * @param array|null $opts
	 * @return CollectionDto|null
	 */
	public function run(mixed $data = null, ?array $opts = null): CollectionDto | null
	{
		$productId = $data['id'];
		$product = Product::findById($productId);
		$messages = $product->whatsappMessages;

		if($messages->isEmpty()){
			return null;
		}

		return CollectionDto::fromModelCollection($messages, WhatsappMessageDto::class);
	}
}
