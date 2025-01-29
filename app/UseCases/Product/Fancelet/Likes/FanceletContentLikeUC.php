<?php

namespace App\UseCases\Product\Fancelet\Likes;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\Product\Fancelet\FanceletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FanceletContentLikeUC implements UseCaseContract
{
	public function __construct(private readonly FanceletService $fanceletService)
	{

	}

	public function run(mixed $data = null, ?array $opts = null)
	{
		$productId = $data['product_id'];
		$contentId = $data['content_id'];
		$contentType = $data['content_type'] = match ($data['content_type']) {
			'video' => 'video',
			'audio' => 'audio',
			'image' => 'image',
			default => 'text'
		};

		$canLike = $this->fanceletService->canLike($productId, $contentId, $contentType);

		if(!$canLike){
			return false;
		}

		if($contentType == 'video') {
			 return $this->updateLikes('fancelet_content_videos', $productId, $contentId, $contentType);
		}

		if($contentType == 'audio') {
			return $this->updateLikes('fancelet_content_audios', $productId, $contentId, $contentType);
		}

		if($contentType == 'image') {
			return $this->updateLikes('fancelet_content_images', $productId, $contentId, $contentType);
		}

		return $this->updateLikes('fancelet_content_texts', $productId, $contentId, $contentType);
	}

	private function updateLikes(string $table, int $productId, int $contentId, string $contentType){
		return DB::transaction(function () use ($table, $productId, $contentId, $contentType) {
			DB::table($table)
				->where('id', $contentId)
				->increment('likes');

			DB::table('fancelet_content_likes_registry')->insert([
				'product_id' => $productId,
				'content_id' => $contentId,
				'content_type' => $contentType,
				'created_at' => Carbon::now()->format('Y-m-d H:i:s.u')
			]);

			return DB::table($table)
				->where('id', $contentId)
				->value('likes');
		});
	}
}