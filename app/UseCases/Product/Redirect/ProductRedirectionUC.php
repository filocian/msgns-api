<?php

declare(strict_types=1);

namespace App\UseCases\Product\Redirect;

use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;

final readonly class ProductRedirectionUC implements UseCaseContract
{
	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{id: int, password: string}|null $data
	 * @param array|null $opts
	 * @return ProductDto | null
	 * @throws ProductNotFoundException
	 */
	public function run(mixed $data = null, ?array $opts = null): string | null
	{
		$productId = $data['id'];
		$productPassword = $data['password'];

		$product = Product::findByConfigPair($productId, 'password', $productPassword);

		$productDto = ProductDto::fromModel($product);

		if($productDto->target_url){
			$product->update(['usage' => $productDto->usage + 1]);
			return $productDto->target_url;
		}

		if(!$productDto->user){
			return env('FRONT_URL') . '/product/' . $productDto->id . '/register/' . $productDto->password;
		}

		return null;
	}
}
