<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
	Cache::flush();
	config()->set('services.google.places_api_key', 'test-google-key');
});

describe('GET /api/v2/places/search', function () {
	it('returns google place candidates for an authenticated user', function () {
		$user = $this->create_user(['email' => 'places@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake([
			'https://maps.googleapis.com/*' => Http::response([
				'status' => 'OK',
				'candidates' => [[
					'place_id' => 'place-1',
					'name' => 'MSGNS Cafe',
					'formatted_address' => 'Main Street 1',
					'types' => ['cafe', 'food'],
					'rating' => 4.7,
					'user_ratings_total' => 128,
					'geometry' => ['location' => ['lat' => 41.1, 'lng' => 2.1]],
					'photos' => [['photo_reference' => 'photo-1']],
					'opening_hours' => ['open_now' => true],
				]],
			], 200),
		]);

		$this->getJson('/api/v2/places/search?name=MSGNS%20Cafe')
			->assertOk()
			->assertJsonPath('data.candidates.0.place_id', 'place-1')
			->assertJsonPath('data.candidates.0.name', 'MSGNS Cafe')
			->assertJsonPath('data.candidates.0.user_ratings_total', 128)
			->assertJsonPath('data.candidates.0.opening_hours.open_now', true);
	});

	it('serializes nullable google fields as null when they are absent', function () {
		$user = $this->create_user(['email' => 'nullable-places@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake([
			'https://maps.googleapis.com/*' => Http::response([
				'status' => 'OK',
				'candidates' => [[
					'place_id' => 'place-2',
					'name' => 'MSGNS Store',
					'formatted_address' => 'Second Street 2',
					'types' => ['store'],
					'geometry' => [],
				]],
			], 200),
		]);

		$this->getJson('/api/v2/places/search?name=MSGNS%20Store')
			->assertOk()
			->assertJsonPath('data.candidates.0.rating', null)
			->assertJsonPath('data.candidates.0.user_ratings_total', null)
			->assertJsonPath('data.candidates.0.photos', null)
			->assertJsonPath('data.candidates.0.opening_hours', null);
	});

	it('returns an empty candidates list when google responds with zero results', function () {
		$user = $this->create_user(['email' => 'zero-results@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake([
			'https://maps.googleapis.com/*' => Http::response([
				'status' => 'ZERO_RESULTS',
				'candidates' => [],
			], 200),
		]);

		$this->getJson('/api/v2/places/search?name=Unknown%20Place')
			->assertOk()
			->assertExactJson([
				'data' => [
					'candidates' => [],
				],
			]);
	});

	it('requires the legacy name query parameter instead of query', function () {
		$user = $this->create_user(['email' => 'legacy-name@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake();

		$this->getJson('/api/v2/places/search?query=MSGNS')
			->assertStatus(422)
			->assertJsonPath('error.code', 'validation_error');

		Http::assertNothingSent();
	});

	it('returns 422 when name is missing', function () {
		$user = $this->create_user(['email' => 'missing-name@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake();

		$this->getJson('/api/v2/places/search')
			->assertStatus(422)
			->assertJsonPath('error.code', 'validation_error')
			->assertJsonPath('error.context.errors.name.0', 'The name field is required.');

		Http::assertNothingSent();
	});

	it('returns 422 when name is shorter than two characters', function () {
		$user = $this->create_user(['email' => 'short-name@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake();

		$this->getJson('/api/v2/places/search?name=A')
			->assertStatus(422)
			->assertJsonPath('error.code', 'validation_error')
			->assertJsonPath('error.context.errors.name.0', 'The name field must be at least 2 characters.');

		Http::assertNothingSent();
	});

	it('returns 401 when the user is unauthenticated', function () {
		Http::fake();

		$this->getJson('/api/v2/places/search?name=MSGNS')
			->assertStatus(401);
	});

	it('sends the trimmed name to google places and uses the configured fields', function () {
		$user = $this->create_user(['email' => 'trimmed-name@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake([
			'https://maps.googleapis.com/*' => Http::response([
				'status' => 'OK',
				'candidates' => [],
			], 200),
		]);

		$this->getJson('/api/v2/places/search?name=%20%20Trimmed%20Place%20%20')->assertOk();

		Http::assertSent(function (Request $request): bool {
			$url = $request->url();
			$hasInput = str_contains($url, 'input=Trimmed+Place') || str_contains($url, 'input=Trimmed%20Place');

			return $hasInput
				&& str_contains($url, 'inputtype=textquery')
				&& str_contains($url, 'user_ratings_total')
				&& str_contains($url, 'key=test-google-key');
		});
	});

	it('returns 502 when the google places http request fails', function () {
		$user = $this->create_user(['email' => 'http-error@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake([
			'https://maps.googleapis.com/*' => Http::response(['message' => 'boom'], 500),
		]);

		$this->getJson('/api/v2/places/search?name=MSGNS')
			->assertStatus(502)
			->assertJsonPath('error.code', 'google_places_http_error');
	});

	it('returns 502 when google responds with a non-ok status payload', function () {
		$user = $this->create_user(['email' => 'request-denied@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake([
			'https://maps.googleapis.com/*' => Http::response([
				'status' => 'REQUEST_DENIED',
				'error_message' => 'API key not valid',
			], 200),
		]);

		$this->getJson('/api/v2/places/search?name=MSGNS')
			->assertStatus(502)
			->assertJsonPath('error.code', 'google_places_request_denied');
	});

	it('returns 502 when the google places api key is not configured', function () {
		$user = $this->create_user(['email' => 'missing-key@example.com']);
		$this->actingAs($user, 'stateful-api');
		config()->set('services.google.places_api_key', null);

		Http::fake();

		$this->getJson('/api/v2/places/search?name=MSGNS')
			->assertStatus(502)
			->assertJsonPath('error.code', 'google_places_api_key_missing');

		Http::assertNothingSent();
	});

	it('returns 422 when name exceeds 255 characters', function () {
		$user = $this->create_user(['email' => 'long-name@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake();

		$this->getJson('/api/v2/places/search?name=' . str_repeat('A', 256))
			->assertStatus(422)
			->assertJsonPath('error.code', 'validation_error');

		Http::assertNothingSent();
	});

	it('caches the result and does not call google api on the second request', function () {
		$user = $this->create_user(['email' => 'cache-test@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake([
			'https://maps.googleapis.com/*' => Http::response([
				'status' => 'OK',
				'candidates' => [[
					'place_id' => 'cached-place',
					'name' => 'Cached Place',
					'formatted_address' => 'Cache Street 1',
					'types' => ['cafe'],
					'geometry' => ['location' => ['lat' => 40.0, 'lng' => -3.0]],
				]],
			], 200),
		]);

		$first = $this->getJson('/api/v2/places/search?name=Cached%20Place');
		$first->assertOk()->assertJsonPath('data.candidates.0.place_id', 'cached-place');

		$second = $this->getJson('/api/v2/places/search?name=Cached%20Place');
		$second->assertOk()->assertJsonPath('data.candidates.0.place_id', 'cached-place');

		Http::assertSentCount(1);
	});

	it('returns 429 after exceeding the rate limit', function () {
		$user = $this->create_user(['email' => 'rate-limit@example.com']);
		$this->actingAs($user, 'stateful-api');

		Http::fake([
			'https://maps.googleapis.com/*' => Http::response([
				'status' => 'OK',
				'candidates' => [],
			], 200),
		]);

		foreach (range(1, 12) as $i) {
			$this->getJson('/api/v2/places/search?name=Rate%20Limit%20' . $i)
				->assertOk();
		}

		$this->getJson('/api/v2/places/search?name=Rate%20Limit%2013')
			->assertStatus(429);
	});
});
