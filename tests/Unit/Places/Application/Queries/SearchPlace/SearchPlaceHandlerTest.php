<?php

declare(strict_types=1);

use Mockery\Expectation;
use Mockery\MockInterface;
use Src\Places\Application\Queries\SearchPlace\SearchPlaceHandler;
use Src\Places\Application\Queries\SearchPlace\SearchPlaceQuery;
use Src\Places\Domain\Ports\GooglePlacesPort;
use Src\Places\Domain\ValueObjects\PlaceSearchResult;
use Src\Shared\Core\Ports\CachePort;

afterEach(fn () => Mockery::close());

describe('SearchPlaceHandler', function () {
	it('returns the cached result without calling google places again', function () {
		$result = PlaceSearchResult::fromArray([
			'candidates' => [
				[
					'place_id' => 'cached-place',
					'name' => 'Cached Place',
					'formatted_address' => 'Cache Street 1',
					'types' => ['cafe'],
					'rating' => 4.8,
					'user_ratings_total' => 44,
					'geometry' => [],
					'photos' => null,
					'opening_hours' => null,
				],
			],
		]);

		/** @var MockInterface&GooglePlacesPort $googlePlaces */
		$googlePlaces = Mockery::mock(GooglePlacesPort::class);
		$googlePlaces->shouldNotReceive('search');

		/** @var MockInterface&CachePort $cache */
		$cache = Mockery::mock(CachePort::class);
		/** @var Expectation $expectation */
		$expectation = $cache->shouldReceive('remember');
		$expectation
			->once()
			->withArgs(function (string $key, int $ttl, Closure $callback): bool {
				return $key === 'places:search:17:' . md5('my favorite cafe')
					&& $ttl === 1800
					&& $callback instanceof Closure;
			})
			->andReturn($result);

		$handler = new SearchPlaceHandler($googlePlaces, $cache);

		expect($handler->handle(new SearchPlaceQuery(userId: 17, name: '  My Favorite Cafe  ')))->toBe($result);
	});

	it('calls google places on cache miss with the trimmed query and returns the resolved result', function () {
		$result = PlaceSearchResult::fromArray([
			'candidates' => [
				[
					'place_id' => 'fresh-place',
					'name' => 'Fresh Place',
					'formatted_address' => 'Fresh Street 2',
					'types' => ['store'],
					'rating' => null,
					'user_ratings_total' => null,
					'geometry' => [],
					'photos' => null,
					'opening_hours' => null,
				],
			],
		]);

		/** @var MockInterface&GooglePlacesPort $googlePlaces */
		$googlePlaces = Mockery::mock(GooglePlacesPort::class);
		$googlePlaces
			->shouldReceive('search')
			->once()
			->with('Fresh Place')
			->andReturn($result);

		/** @var MockInterface&CachePort $cache */
		$cache = Mockery::mock(CachePort::class);
		$cache
			->shouldReceive('remember')
			->once()
			->withArgs(function (string $key, int $ttl): bool {
				return $key === 'places:search:9:' . md5('fresh place')
					&& $ttl === 1800;
			})
			->andReturnUsing(static fn (string $key, int $ttl, Closure $callback): PlaceSearchResult => $callback());

		$handler = new SearchPlaceHandler($googlePlaces, $cache);

		expect($handler->handle(new SearchPlaceQuery(userId: 9, name: '  Fresh Place  '))->toArray())
			->toBe($result->toArray());
	});

	it('normalizes the cache key using lowercase and trimmed name variants', function () {
		/** @var MockInterface&GooglePlacesPort $googlePlaces */
		$googlePlaces = Mockery::mock(GooglePlacesPort::class);
		$googlePlaces->shouldReceive('search')->once()->with('Mixed Case')->andReturn(PlaceSearchResult::fromArray([
			'candidates' => [],
		]));

		/** @var MockInterface&CachePort $cache */
		$cache = Mockery::mock(CachePort::class);
		$cache
			->shouldReceive('remember')
			->once()
			->withArgs(function (string $key, int $ttl, Closure $callback): bool {
				return $key === 'places:search:33:' . md5('mixed case') && $ttl === 1800;
			})
			->andReturnUsing(static fn (string $key, int $ttl, Closure $callback): PlaceSearchResult => $callback());

		$handler = new SearchPlaceHandler($googlePlaces, $cache);

		$result = $handler->handle(new SearchPlaceQuery(userId: 33, name: "\n Mixed Case \t"));

		expect($result->isEmpty())->toBeTrue();
	});
});
