<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Product\Fancelet;

use App\Infrastructure\DTO\Fancelet\FanceletContentGalleryDto;
use App\Infrastructure\DTO\Fancelet\FanceletGroupCommentsDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\DynamoDb\DynamoDbService;
use App\Models\Fancelet\FanceletContentGallery;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

final readonly class FanceletService
{
	public function __construct(private DynamoDbService $dynamoDbService) {}
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

	public function sendComment(int $productId, string $productGroup, string $comment): bool
	{
		$this->dynamoDbService->addFanceletComment($productId, $productGroup, $comment);

		return true;
	}

	public function getGroupComments(string $groupId): FanceletGroupCommentsDto
	{
		return $this->dynamoDbService->getFanceletGroupComments($groupId);
	}
}
