<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Fancelet;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use App\Infrastructure\DTO\ProductDto;

final class FanceletContentDto extends BaseDTO
{
	public ProductDto $product;
	public array|null $images;
	public array|null $texts;
	public array|null $videos;
	public array|null $audios;

	public function __construct(array $data)
	{
		$this->product = $data['product'] ?? null;
		$this->images = $data['images'] ?? null;
		$this->texts = $data['texts'] ?? null;
		$this->videos = $data['videos'] ?? null;
		$this->audios = $data['audios'] ?? null;
	}
}
