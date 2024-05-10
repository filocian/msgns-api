<?php

declare(strict_types=1);

namespace App\Static\Product;

final class StaticProductTypes
{
	private const MSGNS_PRODUCTS = [
		[
			'code' => 'S-GG-XX-RC',
			'type' => 'sticker',
			'model' => ['google'],
		],
		[
			'code' => 'S-GG-XX-RD',
			'type' => 'sticker',
			'model' => ['google'],
		],
		[
			'code' => 'S-GW-XX-RC',
			'type' => 'sticker',
			'model' => ['google'],
		],
		[
			'code' => 'S-GW-XX-RD',
			'type' => 'sticker',
			'model' => ['google'],
		],
		[
			'code' => 'S-IG-XX-RC',
			'type' => 'sticker',
			'model' => ['instagram'],
		],
		[
			'code' => 'S-IG-XX-RD',
			'type' => 'sticker',
			'model' => ['instagram'],
		],
		[
			'code' => 'S-IG-XX-SQ',
			'type' => 'sticker',
			'model' => ['instagram'],
		],
		[
			'code' => 'S-FB-XX-RC',
			'type' => 'sticker',
			'model' => ['facebook'],
		],
		[
			'code' => 'S-FB-XX-RD',
			'type' => 'sticker',
			'model' => ['facebook'],
		],
		[
			'code' => 'S-YT-XX-RC',
			'type' => 'sticker',
			'model' => ['youtube'],
		],
		[
			'code' => 'S-TK-XX-RC',
			'type' => 'sticker',
			'model' => ['tiktok'],
		],
		[
			'code' => 'S-IN-XX-RC',
			'type' => 'sticker',
			'model' => ['info'],
		],
		[
			'code' => 'S-WR-XX-RC',
			'type' => 'sticker',
			'model' => ['whatsapp'],
		],
		[
			'code' => 'S-WC-XX-RC',
			'type' => 'sticker',
			'model' => ['whatsapp'],
		],
		[
			'code' => 'S-WG-XX-RC',
			'type' => 'sticker',
			'model' => ['whatsapp'],
		],
		[
			'code' => 'S-WW-XX-SQ',
			'type' => 'sticker',
			'model' => ['whatsapp'],
		],
		[
			'code' => 'S-WG-XX-SQ',
			'type' => 'sticker',
			'model' => ['whatsapp'],
		],
		[
			'code' => 'P-GG-IN-RC',
			'type' => 'card',
			'model' => ['google', 'info'],
		],
		[
			'code' => 'P-GW-IN-RC',
			'type' => 'card',
			'model' => ['google', 'info'],
		],
		[
			'code' => 'P-GW-GO-RC',
			'type' => 'card',
			'model' => ['google', ],
		],
		[
			'code' => 'P-GM-GO-RC',
			'type' => 'card',
			'model' => ['google'],
		],
		[
			'code' => 'T-GW-XX-RC',
			'type' => 'stand',
			'model' => ['google'],
		],
	];

	/**
	 * @param array{code:string, type:string, model:array<string>} $msgnsProduct
	 * @return array
	 */
	private static function buildProductStructure(array $msgnsProduct): array
	{
		$models = count($msgnsProduct['model']) > 1
			? implode('+', $msgnsProduct['model'])
			: $msgnsProduct['model'][0];
		$baseConfig = [
			'image_ref' => $msgnsProduct['code'],
			'password' => '123456',
		];
		$targetConfig = [];

		for ($x = 0; $x < count($msgnsProduct['model']); $x++) {
			$key = "{$msgnsProduct['model'][$x]}_url";
			$targetConfig[$key] = 'https://target.url';
		}

		return [
			'code' => $msgnsProduct['code'],
			'name' => $msgnsProduct['code'],
			'description' => "{$msgnsProduct['type']} $models",
			'config' => array_merge($targetConfig, $baseConfig),
		];
	}

	public static function all()
	{
		$products = [];
		foreach (self::MSGNS_PRODUCTS as $product) {
			$products[] = self::buildProductStructure($product);
		}
		return $products;
	}
}
