<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class UpdateLoveImagesWithNewImagesAndOrderForDayOfMonthSeeder extends Seeder
{
	protected $imagesTable = 'fancelet_content_images';
	protected $galleryTable = 'fancelet_content_gallery';
	private array $images = [
		'https://fancelets.s3.eu-west-3.amazonaws.com/LOB1/images/022.png',
		'https://fancelets.s3.eu-west-3.amazonaws.com/LOB1/images/023.png',
		'https://fancelets.s3.eu-west-3.amazonaws.com/LOB1/images/024.png',
		'https://fancelets.s3.eu-west-3.amazonaws.com/LOB1/images/025.png',
		'https://fancelets.s3.eu-west-3.amazonaws.com/LOB1/images/026.png',
		'https://fancelets.s3.eu-west-3.amazonaws.com/LOB1/images/027.png',
		'https://fancelets.s3.eu-west-3.amazonaws.com/LOB1/images/028.png',
		'https://fancelets.s3.eu-west-3.amazonaws.com/LOB1/images/029.png',
		'https://fancelets.s3.eu-west-3.amazonaws.com/LOB1/images/030.png',
		'https://fancelets.s3.eu-west-3.amazonaws.com/LOB1/images/031.png',
	];

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$contentId = DB::table($this->galleryTable)
			->where('product_type_id', 26)
			->value('id');

		foreach ($this->images as $imageUrl) {
			DB::table($this->imagesTable)->insert([
				'gallery_id' => $contentId,
				'url' => $imageUrl,
			]);
		}

		for ($x = 1; $x < 32; $x++) {
			DB::table($this->imagesTable)->where([
				'gallery_id' => $contentId,
				'id' => $x,
			])->update(['order' => $x]);
		}
	}
}
