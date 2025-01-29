<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Fancelet;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use App\Infrastructure\DTO\ProductDto;

final class FanceletDto extends BaseDTO
{
	public ProductDto $product;
	public FanceletContentGalleryDto $fanceletContent;
}
