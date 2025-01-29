<?php

namespace App\Infrastructure\Services\Product\Fancelet;

use App\Infrastructure\DTO\Fancelet\FanceletContentGalleryDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\DynamoDb\DynamoDbService;
use App\Models\Fancelet\FanceletContentGallery;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class FanceletService
{
	public function __construct	(private readonly DynamoDbService $dynamoDbService)
	{

	}
	public function getContentGallery(int $productId, string $password): FanceletContentGalleryDto
	{
		$product = Product::findByConfigPair($productId, 'password', $password);
		$productDto = ProductDto::fromModel($product);
		$contentGallery = FanceletContentGallery::findByProductTypeId($productDto->type->id);

		return new FanceletContentGalleryDto($contentGallery, $productDto);
	}

	public function canLike(int $productId, int $contentId, string $contentType): bool{
		$count = DB::table('fancelet_content_likes_registry')
			->where('product_id', $productId)
			->where('content_id', $contentId)
			->where('content_type', $contentType)
			->count();

		return $count < 1;
	}

	public function sendComment(int $productId, string $productPassword, string $comment): bool{
		$this->dynamoDbService->addFanceletComment($productId, $productPassword, $comment);

		return true;
	}
}