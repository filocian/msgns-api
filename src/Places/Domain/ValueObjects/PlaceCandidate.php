<?php

declare(strict_types=1);

namespace Src\Places\Domain\ValueObjects;

/**
 * @phpstan-type PlaceCandidateArray array{
 *     place_id: string,
 *     name: string,
 *     formatted_address: string,
 *     types: list<string>,
 *     rating: ?float,
 *     user_ratings_total: ?int,
 *     geometry: array<string, mixed>,
 *     photos: ?array<int, array<string, mixed>>,
 *     opening_hours: ?array<string, mixed>
 * }
 */
final readonly class PlaceCandidate
{
	/**
	 * @param list<string> $types
	 * @param array<string, mixed> $geometry
	 * @param array<int, array<string, mixed>>|null $photos
	 * @param array<string, mixed>|null $openingHours
	 */
	public function __construct(
		public string $placeId,
		public string $name,
		public string $formattedAddress,
		public array $types,
		public ?float $rating,
		public ?int $userRatingsTotal,
		public array $geometry,
		public ?array $photos,
		public ?array $openingHours,
	) {}

	/**
	 * @param PlaceCandidateArray $data
	 */
	public static function fromArray(array $data): self
	{
		return new self(
			placeId: $data['place_id'],
			name: $data['name'],
			formattedAddress: $data['formatted_address'],
			types: $data['types'],
			rating: $data['rating'],
			userRatingsTotal: $data['user_ratings_total'],
			geometry: $data['geometry'],
			photos: $data['photos'],
			openingHours: $data['opening_hours'],
		);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromGoogleResponse(array $data): self
	{
		$rating = $data['rating'] ?? null;
		$userRatingsTotal = $data['user_ratings_total'] ?? null;

		return new self(
			placeId: (string) ($data['place_id'] ?? ''),
			name: (string) ($data['name'] ?? ''),
			formattedAddress: (string) ($data['formatted_address'] ?? ''),
			types: self::normalizeTypes($data['types'] ?? []),
			rating: is_numeric($rating) ? (float) $rating : null,
			userRatingsTotal: is_numeric($userRatingsTotal) ? (int) $userRatingsTotal : null,
			geometry: is_array($data['geometry'] ?? null) ? $data['geometry'] : [],
			photos: self::normalizePhotos($data['photos'] ?? null),
			openingHours: is_array($data['opening_hours'] ?? null) ? $data['opening_hours'] : null,
		);
	}

	/**
	 * @return PlaceCandidateArray
	 */
	public function toArray(): array
	{
		return [
			'place_id' => $this->placeId,
			'name' => $this->name,
			'formatted_address' => $this->formattedAddress,
			'types' => $this->types,
			'rating' => $this->rating,
			'user_ratings_total' => $this->userRatingsTotal,
			'geometry' => $this->geometry,
			'photos' => $this->photos,
			'opening_hours' => $this->openingHours,
		];
	}

	/**
	 * @param mixed $types
	 * @return list<string>
	 */
	private static function normalizeTypes(mixed $types): array
	{
		if (!is_array($types)) {
			return [];
		}

		return array_values(array_map(static fn (mixed $type): string => (string) $type, $types));
	}

	/**
	 * @param mixed $photos
	 * @return array<int, array<string, mixed>>|null
	 */
	private static function normalizePhotos(mixed $photos): ?array
	{
		if (!is_array($photos)) {
			return null;
		}

		return array_values(array_filter($photos, static fn (mixed $photo): bool => is_array($photo)));
	}
}
