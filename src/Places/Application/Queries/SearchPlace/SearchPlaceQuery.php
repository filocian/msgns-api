<?php

declare(strict_types=1);

namespace Src\Places\Application\Queries\SearchPlace;

use Src\Shared\Core\Bus\Query;

final readonly class SearchPlaceQuery implements Query
{
	public function __construct(
		public int $userId,
		public string $name,
	) {}

	public function queryName(): string
	{
		return 'places.search_place';
	}
}
