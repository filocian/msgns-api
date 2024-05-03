<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseCollectionDTO;
use App\Infrastructure\Contracts\DTO\Interfaces\DTO;
use Illuminate\Support\Collection;

final class CollectionDto extends BaseCollectionDTO
{
	/**
	 * @param Collection<DTO> $data
	 */
	public function __construct(
		public Collection $data,
	) {}
}
