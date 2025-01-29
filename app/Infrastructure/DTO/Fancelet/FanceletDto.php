<?php

namespace App\Infrastructure\DTO\Fancelet;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use App\Infrastructure\DTO\ProductDto;

class FanceletDto extends BaseDto
{
	public ProductDto $product;
	public FanceletContentGalleryDto $fanceletContent;

}