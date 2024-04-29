<?php

declare(strict_types=1);

namespace App\Static\Product;

final class StaticProductTypes
{
	public const YT_SUBSCRIBE_STICKER = [
		'code' => 'youtube-subscribe-sticker',
		'name' => 'youtube-subscribe-sticker',
		'description' => 'youtube-subscribe-sticker',
		'config' => [
			'product_image' => 'product_image_path_or_url',
			'target' => '',
			'target2' => '',
			'password' => '123456',
		],
	];
	public const FB_STICKER = [
		'code' => 'facebook-sticker',
		'name' => 'facebook-sticker',
		'description' => 'facebook-sticker',
		'config' => [
			'product_image' => 'product_image_path_or_url',
			'target' => '',
			'password' => '123456',
		],
	];
	public const TT_STICKER = [
		'code' => 'tiktok-sticker',
		'name' => 'tiktok-sticker',
		'description' => 'tiktok-sticker',
		'config' => [
			'product_image' => 'product_image_path_or_url',
			'target' => '',
			'password' => '123456',
		],
	];
	public const GR_STICKER = [
		'code' => 'google-review-sticker',
		'name' => 'google-review-sticker',
		'description' => 'google-review-sticker',
		'config' => [
			'product_image' => 'product_image_path_or_url',
			'target' => '',
			'password' => '123456',
		],
	];

	public const IG_STICKER = [
		'code' => 'instagram-sticker',
		'name' => 'instagram-sticker',
		'description' => 'instagram-sticker',
		'config' => [
			'product_image' => 'product_image_path_or_url',
			'target' => '',
			'password' => '123456',
		],
	];
	public const IG_STICKER_ROUND = [
		'code' => 'instagram-sticker-round',
		'name' => 'instagram-sticker-round',
		'description' => 'instagram-sticker-round',
		'config' => [
			'product_image' => 'product_image_path_or_url',
			'target' => '',
			'password' => '123456',
		],
	];
	public const IG_STICKER_SQUARE = [
		'code' => 'instagram-sticker-square',
		'name' => 'instagram-sticker-square',
		'description' => 'instagram-sticker-square',
		'config' => [
			'product_image' => 'product_image_path_or_url',
			'target' => '',
			'password' => '123456',
		],
	];

	public const WS_STICKER = [
		'code' => 'whatsapp-sticker',
		'name' => 'whatsapp-sticker',
		'description' => 'whatsapp-sticker',
		'config' => [
			'product_image' => 'product_image_path_or_url',
			'target' => '',
			'password' => '123456',
		],
	];
	public const WS_STICKER_SQUARE = [
		'code' => 'whatsapp-sticker-square',
		'name' => 'whatsapp-sticker-square',
		'description' => 'whatsapp-sticker-square',
		'config' => [
			'product_image' => 'product_image_path_or_url',
			'target' => '',
			'password' => '123456',
		],
	];
	public const WS_STICKER_WHITESQUARE = [
		'code' => 'whatsapp-sticker-whitesquare',
		'name' => 'whatsapp-sticker-whitesquare',
		'description' => 'whatsapp-sticker-whitesquare',
		'config' => [
			'product_image' => 'product_image_path_or_url',
			'target' => '',
			'password' => '123456',
		],
	];


	public static function all()
	{
		return [
			self::YT_SUBSCRIBE_STICKER,
			self::FB_STICKER,
			self::TT_STICKER,
			self::GR_STICKER,
			self::IG_STICKER,
			self::IG_STICKER_ROUND,
			self::IG_STICKER_SQUARE,
			self::WS_STICKER,
			self::WS_STICKER_SQUARE,
			self::WS_STICKER_WHITESQUARE,
		];
	}
}
