<?php

declare(strict_types=1);

use Src\Places\Domain\ValueObjects\PlaceCandidate;

describe('PlaceCandidate', function () {
	it('creates a candidate from a google places payload', function () {
		$candidate = PlaceCandidate::fromGoogleResponse([
			'place_id' => 'place-1',
			'name' => 'MSGNS Cafe',
			'formatted_address' => 'Main Street 1',
			'types' => ['cafe', 'food'],
			'rating' => 4.7,
			'user_ratings_total' => 128,
			'geometry' => [
				'location' => ['lat' => 41.1, 'lng' => 2.1],
			],
			'photos' => [
				['photo_reference' => 'photo-1'],
			],
			'opening_hours' => ['open_now' => true],
		]);

		expect($candidate->placeId)->toBe('place-1')
			->and($candidate->name)->toBe('MSGNS Cafe')
			->and($candidate->formattedAddress)->toBe('Main Street 1')
			->and($candidate->types)->toBe(['cafe', 'food'])
			->and($candidate->rating)->toBe(4.7)
			->and($candidate->userRatingsTotal)->toBe(128)
			->and($candidate->geometry)->toBe([
				'location' => ['lat' => 41.1, 'lng' => 2.1],
			])
			->and($candidate->photos)->toBe([
				['photo_reference' => 'photo-1'],
			])
			->and($candidate->openingHours)->toBe(['open_now' => true]);
	});

	it('keeps nullable fields as null and serializes snake_case keys', function () {
		$candidate = PlaceCandidate::fromArray([
			'place_id' => 'place-2',
			'name' => 'MSGNS Shop',
			'formatted_address' => 'Second Street 2',
			'types' => ['store'],
			'rating' => null,
			'user_ratings_total' => null,
			'geometry' => [],
			'photos' => null,
			'opening_hours' => null,
		]);

		expect($candidate->toArray())->toBe([
			'place_id' => 'place-2',
			'name' => 'MSGNS Shop',
			'formatted_address' => 'Second Street 2',
			'types' => ['store'],
			'rating' => null,
			'user_ratings_total' => null,
			'geometry' => [],
			'photos' => null,
			'opening_hours' => null,
		]);
	});
});
