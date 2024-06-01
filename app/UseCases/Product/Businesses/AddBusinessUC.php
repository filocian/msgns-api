<?php

declare(strict_types=1);

namespace App\UseCases\Product\Businesses;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductBusinessDto;
use App\Models\ProductBusiness;

final readonly class AddBusinessUC implements UseCaseContract
{
	public function __construct()
	{
	}

	/**
	 * UseCase: Assign a product to given user
	 *
	 * @param mixed $data
	 * @param array|null $opts
	 * @return ProductBusinessDto
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductBusinessDto
	{
		$businessData = [
			'types' => $data['types'] ?? [],
			'place_types' => $data['placeTypes'] ?? [],

		];

		if(isset($data['name'])){
			$businessData['name'] = $data['name'];
		}

		if(isset($data['size'])){
			$businessData['size'] = $data['size'];
		}

		$business = ProductBusiness::updateOrCreate(
			[
				'product_id' => $data['productId'],
				'user_id' => $data['userId'],
			],
			$businessData
		);

//		$name = $data['name'];
//		$size = $data['size'];
//		$types = $data['types'];
//		$place_types = $data['place_types'] ?? null;
//		$productId = $data['productId'];
//		$userId = $data['userId'];
//
//		$business = new ProductBusiness;
//		$business->product_id = $productId;
//		$business->user_id = $userId;
//		$business->name = $name;
//		$business->types = $types;
//		$business->place_types = $place_types;
//		$business->size = $size;
//		$business->save();

		return ProductBusinessDto::fromModel($business);
	}
}
