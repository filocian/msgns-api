<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class Fancelet_YOB1IWSeeder extends Seeder
{
	protected $galleryTable = 'fancelet_content_gallery';
	protected $videosTable = 'fancelet_content_videos';
	private array $videos = [
		1 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-1.mp4'],
		2 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-2.mp4'],
		3 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-3.mp4'],
		4 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-4.mp4'],
		5 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-5.mp4'],
		6 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-6.mp4'],
		7 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-7.mp4'],
		8 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-8.mp4'],
		9 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-9.mp4'],
		10 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-10.mp4'],
		11 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-11.mp4'],
		12 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-12.mp4'],
		13 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-13.mp4'],
		14 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-14.mp4'],
		15 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-15.mp4'],
		16 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-16.mp4'],
		17 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-17.mp4'],
		18 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-18.mp4'],
		19 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-19.mp4'],
		20 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-20.mp4'],
		21 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-21.mp4'],
		22 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-22.mp4'],
		23 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-23.mp4'],
		24 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-24.mp4'],
		25 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-25.mp4'],
		26 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-26.mp4'],
		27 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-27.mp4'],
		28 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/YOB1/YO-28.mp4'],
	];

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		DB::table($this->galleryTable)->updateOrInsert([
			'product_type_id' => 24,
		]);

		$contentId = DB::table($this->galleryTable)
			->where('product_type_id', 24)
			->value('id');

		foreach ($this->videos as $order => $content) {
			$data = [
				'gallery_id' => $contentId,
				'order' => $order,
			];

			foreach ($content as $locale => $url) {
				$data[$locale] = $url;
			}

			DB::table($this->videosTable)->insert($data);
		}
	}
}
