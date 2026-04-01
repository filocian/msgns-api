<?php

declare(strict_types=1);

use Src\Places\Domain\ValueObjects\PlaceSearchResult;

describe('PlaceSearchResult', function () {
	it('builds a non-empty search result from the google response payload', function () {
		$result = PlaceSearchResult::fromGoogleResponse([
			'candidates' => [
				[
					'place_id' => 'place-1',
					'name' => 'MSGNS Cafe',
					'formatted_address' => 'Main Street 1',
					'types' => ['cafe'],
					'rating' => 4.5,
					'user_ratings_total' => 25,
					'geometry' => ['location' => ['lat' => 1.0, 'lng' => 2.0]],
					'photos' => null,
					'opening_hours' => ['open_now' => false],
				],
			],
		]);

		expect($result->isEmpty())->toBeFalse()
			->and($result->toArray())->toBe([
				'candidates' => [
					[
						'place_id' => 'place-1',
						'name' => 'MSGNS Cafe',
						'formatted_address' => 'Main Street 1',
						'types' => ['cafe'],
						'rating' => 4.5,
						'user_ratings_total' => 25,
						'geometry' => ['location' => ['lat' => 1.0, 'lng' => 2.0]],
						'photos' => null,
						'opening_hours' => ['open_now' => false],
					],
				],
			]);
	});

	it('returns an empty result when google responds with zero candidates', function () {
		$result = PlaceSearchResult::fromGoogleResponse([
			'candidates' => [],
		]);

		expect($result->isEmpty())->toBeTrue()
			->and($result->toArray())->toBe(['candidates' => []]);
	});
});
