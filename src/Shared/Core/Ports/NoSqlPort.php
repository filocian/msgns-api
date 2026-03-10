<?php

declare(strict_types=1);

namespace Src\Shared\Core\Ports;

/**
 * Contract for shared NoSQL access.
 */
interface NoSqlPort
{
	/**
	 * @param array<string, mixed> $key
	 * @return array<string, mixed>|null
	 */
	public function getItem(string $table, array $key): ?array;

	/**
	 * @param array<string, mixed> $item
	 */
	public function putItem(string $table, array $item): void;

	/**
	 * @param array<string, mixed> $key
	 */
	public function deleteItem(string $table, array $key): void;

	/**
	 * @param array<string, mixed> $keyCondition
	 * @param array<string, mixed>|null $filter
	 * @param array<string, mixed>|null $exclusiveStartKey
	 */
	public function query(
		string $table,
		array $keyCondition,
		?array $filter = null,
		?int $limit = null,
		?array $exclusiveStartKey = null,
	): NoSqlQueryResult;
}
