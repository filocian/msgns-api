<?php

declare(strict_types=1);

namespace App\UseCases\Product\Whatsapp;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\CollectionDto;
use App\Infrastructure\DTO\Whatsapp\WhatsappPhoneDto;
use App\Models\Product;

final readonly class ListPhonesUC implements UseCaseContract
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
		$phones = $product->whatsappPhones;

		if($phones->isEmpty()){
			return null;
		}

		return CollectionDto::fromModelCollection($phones, WhatsappPhoneDto::class);
	}
}
