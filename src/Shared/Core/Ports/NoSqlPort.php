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

	/**
	 * Delete all items matching a key condition query, in paginated 25-item batches with retry.
	 * Retries only unprocessed items with exponential backoff and jitter, up to 5 attempts per chunk.
	 * Throws \RuntimeException if any chunk still has unprocessed items after the retry ceiling.
	 *
	 * @param array<string, mixed> $keyCondition  Query params (KeyConditionExpression, ExpressionAttributeValues, etc.)
	 * @param array<string> $keySchema            Attribute names forming the composite key (e.g. ['productId', 'scannedAt'])
	 * @throws \RuntimeException When unprocessed items remain after 5 retries for any chunk.
	 */
	public function batchDeleteByQuery(string $table, array $keyCondition, array $keySchema): void;
}
