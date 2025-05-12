<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Product\Fancelet;

use App\Infrastructure\DTO\Fancelet\FanceletContentDto;
use App\Infrastructure\DTO\Fancelet\FanceletContentGalleryDto;
use App\Infrastructure\DTO\Fancelet\FanceletGroupCommentsDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\Services\DynamoDb\DynamoDbService;
use App\Models\Fancelet\FanceletContentGallery;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class FanceletService
{
	public function __construct(private DynamoDbService $dynamoDbService, private AuthService $authService) {}
	public function getContentGallery(int $productId, string $password): FanceletContentGalleryDto
	{
		$product = Product::findByConfigPair($productId, 'password', $password);
		$productDto = ProductDto::fromModel($product);
		$contentGallery = FanceletContentGallery::findByProductTypeId($productDto->type->id);

		return new FanceletContentGalleryDto($contentGallery, $productDto);
	}

	public function canLike(int $productId, int $contentId, string $contentType): bool
	{
		$count = DB::table('fancelet_content_likes_registry')
			->where('product_id', $productId)
			->where('content_id', $contentId)
			->where('content_type', $contentType)
			->count();

		return $count < 1;
	}

	public function sendComment(int $productId, string $productGroup, string $comment, array|null $tags = null): bool
	{
		$this->dynamoDbService->addFanceletComment($productId, $productGroup, $comment, $tags);

		return true;
	}

	public function getGroupComments(
		string $groupId,
		array|null $includeTags = null,
		array|null $filterTags = null
	): FanceletGroupCommentsDto {
		return $this->dynamoDbService->getFanceletGroupComments($groupId, $includeTags, $filterTags);
	}

	public function getFanceletAvailableVideos(int $productId, string $productPwd)
	{
		$contentGalleryDto = $this->getContentGallery($productId, $productPwd);
		$userId = $this->authService->id();

		$videos = DB::table('fancelet_content_videos')
			->where('gallery_id', $contentGalleryDto->gallery_id)
			->orderBy('order', 'asc')
			->get();

		$watchedIds = DB::table('fancelet_user_video_history')
			->where('product_id', $productId)
			->where('user_id', $userId)
			->orderBy('video_id', 'asc')
			->pluck('video_id')
			->all();

		$viewed = $videos->filter(function ($video) use ($watchedIds) {
			return in_array($video->id, $watchedIds, true);
		})->values();

		$nextUnseen = $videos->first(function ($video) use ($watchedIds) {
			return !in_array($video->id, $watchedIds, true);
		});

		$result = $viewed;
		if ($nextUnseen) {
			$result->push($nextUnseen);
		}

		return new FanceletContentDto([
			'product' => $contentGalleryDto->product,
			'videos' => $result->toarray(),
			'metadata' => [
				'total_videos' => $videos->count(),
			],
		]);
	}

	public function markVideoViewed(int $productId, int $videoId, string $productPwd): FanceletContentDto
	{
		$timestamp = Carbon::now()->toDateTimeString();
		$userId = $this->authService->id();
		DB::table('fancelet_user_video_history')->updateOrInsert([
			'product_id' => $productId,
			'video_id' => $videoId,
			'user_id' => $userId,
			'created_at' => $timestamp,
			'updated_at' => $timestamp,
		]);

		return $this->getFanceletAvailableVideos($productId, $productPwd);
	}
}
