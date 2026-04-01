<?php

declare(strict_types=1);

namespace Src\Places\Infrastructure\Adapters;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Src\Places\Domain\Errors\GooglePlacesUnavailable;
use Src\Places\Domain\Ports\GooglePlacesPort;
use Src\Places\Domain\ValueObjects\PlaceSearchResult;

final class GooglePlacesAdapter implements GooglePlacesPort
{
	private const ENDPOINT = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';

	private const FIELDS = 'place_id,types,photos,formatted_address,name,rating,user_ratings_total,opening_hours,geometry';

	public function search(string $query): PlaceSearchResult
	{
		$apiKey = (string) config('services.google.places_api_key', '');

		if ($apiKey === '') {
			throw GooglePlacesUnavailable::because('google_places_api_key_missing');
		}

		try {
			$response = Http::timeout(5)->get(self::ENDPOINT, [
				'input' => $query,
				'inputtype' => 'textquery',
				'fields' => self::FIELDS,
				'key' => $apiKey,
			]);
		} catch (ConnectionException) {
			throw GooglePlacesUnavailable::because('google_places_connection_failed');
		}

		if ($response->failed()) {
			throw GooglePlacesUnavailable::because('google_places_http_error');
		}

		$payload = $response->json();

		if (!is_array($payload)) {
			throw GooglePlacesUnavailable::because('google_places_invalid_payload');
		}

		/** @var array<string, mixed> $payload */
		$status = (string) ($payload['status'] ?? '');

		if ($status === 'ZERO_RESULTS') {
			return PlaceSearchResult::fromGoogleResponse(['candidates' => []]);
		}

		if ($status !== 'OK') {
			$reason = strtolower($status) !== '' ? 'google_places_' . strtolower($status) : 'google_places_unknown_error';

			throw GooglePlacesUnavailable::because($reason);
		}

		return PlaceSearchResult::fromGoogleResponse($payload);
	}
}
