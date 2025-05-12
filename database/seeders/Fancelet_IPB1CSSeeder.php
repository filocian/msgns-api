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
		1 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-1.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-1.mp4'],
		2 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-2.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-2.mp4'],
		3 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-3.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-3.mp4'],
		4 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-4.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-4.mp4'],
		5 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-5.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-5.mp4'],
		6 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-6.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-6.mp4'],
		7 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-7.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-7.mp4'],
		8 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-8.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-8.mp4'],
		9 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-9.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-9.mp4'],
		10 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-10.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-10.mp4'],
		11 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-11.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-11.mp4'],
		12 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-12.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-12.mp4'],
		13 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-13.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-13.mp4'],
		14 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-14.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-14.mp4'],
		15 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-15.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-15.mp4'],
		16 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-16.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-16.mp4'],
		17 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-17.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-17.mp4'],
		18 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-18.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-18.mp4'],
		19 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-19.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-19.mp4'],
		20 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-20.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-20.mp4'],
		21 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-21.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-21.mp4'],
		22 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-22.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-22.mp4'],
		23 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-23.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-23.mp4'],
		24 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-24.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-24.mp4'],
		25 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-25.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-25.mp4'],
		26 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-26.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-26.mp4'],
		27 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-27.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-27.mp4'],
		28 => ['es_ES_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-28.mp4', 'en_EN_url' => 'https://cesar-audios.s3.eu-west-3.amazonaws.com/IP-28.mp4'],
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
