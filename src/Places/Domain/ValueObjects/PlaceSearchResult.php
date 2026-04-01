<?php

declare(strict_types=1);

namespace Src\Places\Domain\ValueObjects;

/**
 * @phpstan-import-type PlaceCandidateArray from PlaceCandidate
 * @phpstan-type PlaceSearchResultArray array{candidates: list<PlaceCandidateArray>}
 */
final readonly class PlaceSearchResult
{
	/**
	 * @param list<PlaceCandidate> $candidates
	 */
	public function __construct(public array $candidates) {}

	/**
	 * @param PlaceSearchResultArray $data
	 */
	public static function fromArray(array $data): self
	{
		return new self(
			candidates: array_map(
				static fn (array $candidate): PlaceCandidate => PlaceCandidate::fromArray($candidate),
				$data['candidates'],
			),
		);
	}

	/**
	 * @param array<string, mixed> $response
	 */
	public static function fromGoogleResponse(array $response): self
	{
		$candidates = $response['candidates'] ?? [];

		if (!is_array($candidates)) {
			return new self([]);
		}

		return new self(
			candidates: array_values(array_map(
				static fn (array $candidate): PlaceCandidate => PlaceCandidate::fromGoogleResponse($candidate),
				array_filter($candidates, static fn (mixed $candidate): bool => is_array($candidate)),
			)),
		);
	}

	public function isEmpty(): bool
	{
		return $this->candidates === [];
	}

	/**
	 * @return PlaceSearchResultArray
	 */
	public function toArray(): array
	{
		return [
			'candidates' => array_map(
				static fn (PlaceCandidate $candidate): array => $candidate->toArray(),
				$this->candidates,
			),
		];
	}
}
