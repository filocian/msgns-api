<?php

declare(strict_types=1);

namespace Src\Places\Application\Queries\SearchPlace;

use Src\Places\Domain\Ports\GooglePlacesPort;
use Src\Places\Domain\ValueObjects\PlaceSearchResult;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Shared\Core\Ports\CachePort;

final class SearchPlaceHandler implements QueryHandler
{
	private const CACHE_TTL_SECONDS = 1800;

	public function __construct(
		private readonly GooglePlacesPort $googlePlaces,
		private readonly CachePort $cache,
	) {}

	public function handle(Query $query): PlaceSearchResult
	{
		assert($query instanceof SearchPlaceQuery);

		$normalizedName = trim($query->name);
		$cacheKey = sprintf(
			'places:search:%d:%s',
			$query->userId,
			md5(strtolower($normalizedName)),
		);

		/** @var PlaceSearchResult */
		return $this->cache->remember(
			$cacheKey,
			self::CACHE_TTL_SECONDS,
			fn (): PlaceSearchResult => $this->googlePlaces->search($normalizedName),
		);
	}
}
