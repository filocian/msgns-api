<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class Fancelet_IPB1CSSeeder extends Seeder
{
	protected $galleryTable = 'fancelet_content_gallery';
	protected $videosTable = 'fancelet_content_videos';
	private array $videos = [
		1 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-1.mp4'],
		2 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-2.mp4'],
		3 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-3.mp4'],
		4 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-4.mp4'],
		5 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-5.mp4'],
		6 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-6.mp4'],
		7 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-7.mp4'],
		8 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-8.mp4'],
		9 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-9.mp4'],
		10 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-10.mp4'],
		11 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-11.mp4'],
		12 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-12.mp4'],
		13 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-13.mp4'],
		14 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-14.mp4'],
		15 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-15.mp4'],
		16 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-16.mp4'],
		17 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-17.mp4'],
		18 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-18.mp4'],
		19 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-19.mp4'],
		20 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-20.mp4'],
		21 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-21.mp4'],
		22 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-22.mp4'],
		23 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-23.mp4'],
		24 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-24.mp4'],
		25 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-25.mp4'],
		26 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-26.mp4'],
		27 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-27.mp4'],
		28 => ['en_EN_url' => 'https://fancelets.s3.eu-west-3.amazonaws.com/IPB1/IP-28.mp4'],
	];

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		DB::table($this->galleryTable)->updateOrInsert([
			'product_type_id' => 29,
		]);

		$contentId = DB::table($this->galleryTable)
			->where('product_type_id', 29)
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
