<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Fancelet;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use App\Infrastructure\DTO\ProductDto;
use Illuminate\Database\Eloquent\Model;

final class FanceletContentGalleryDto extends BaseDTO
{
	public int $gallery_id;
	public ProductDto $product;

	public function __construct(Model $model, ProductDto $productDto)
	{
		$this->gallery_id = $model->id;
		$this->product = $productDto;
	}
}
