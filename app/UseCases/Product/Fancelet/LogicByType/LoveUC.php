<?php

declare(strict_types=1);

namespace App\UseCases\Product\Fancelet\LogicByType;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\Fancelet\FanceletContentDto;
use App\Infrastructure\Services\Product\Fancelet\FanceletService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class LoveUC implements UseCaseContract
{
	public function __construct(private readonly FanceletService $fanceletService) {}
	public function run(mixed $data = null, ?array $opts = null)
	{
		$productId = $data['product_id'];
		$productPassword = $data['password'];
		$contentGalleryDto = $this->fanceletService->getContentGallery($productId, $productPassword);
		$yearDay = Carbon::now()->dayOfYear;

		$images = DB::table('fancelet_content_images')
			->select('*')
			->where('gallery_id', $contentGalleryDto->gallery_id)
			->get()
			->toArray();

		$image = $images[rand(0, count($images) - 1)];
		$text = DB::table('fancelet_content_texts')
			->select('*')
			->where('gallery_id', $contentGalleryDto->gallery_id)
			->where('order', $yearDay)
			->get()
			->toArray();

		return new FanceletContentDto([
			'product' => $contentGalleryDto->product,
			'images' => [$image],
			'texts' => $text,
		]);
	}
}
