<?php

declare(strict_types=1);

namespace Src\Shared\Core\Ports;

/**
 * Value object for DynamoDB query results with optional pagination state.
 */
final readonly class NoSqlQueryResult
{
	/**
	 * @param array<int, array<string, mixed>> $items
	 * @param array<string, mixed>|null $lastEvaluatedKey
	 */
	public function __construct(
		public array $items,
		public ?array $lastEvaluatedKey = null,
	) {}

	/**
	 * Indicates whether another page can be requested.
	 */
	public function hasMoreResults(): bool
	{
		return $this->lastEvaluatedKey !== null;
	}
}
