<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\NoSql;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Src\Shared\Core\Ports\NoSqlPort;
use Src\Shared\Core\Ports\NoSqlQueryResult;

/**
 * DynamoDB-backed implementation of the shared NoSQL port.
 */
final class DynamoDbAdapter implements NoSqlPort
{
	private readonly Marshaler $marshaler;

	public function __construct(private readonly DynamoDbClient $client)
	{
		$this->marshaler = new Marshaler();
	}

	/**
	 * @param array<string, mixed> $key
	 * @return array<string, mixed>|null
	 */
	public function getItem(string $table, array $key): ?array
	{
		$result = $this->client->getItem([
			'TableName' => $table,
			'Key' => $this->marshaler->marshalItem($key),
		]);

		if (! isset($result['Item'])) {
			return null;
		}

		/** @var array<string, mixed> $item */
		$item = $this->marshaler->unmarshalItem($result['Item']);

		return $item;
	}

	/**
	 * @param array<string, mixed> $item
	 */
	public function putItem(string $table, array $item): void
	{
		$this->client->putItem([
			'TableName' => $table,
			'Item' => $this->marshaler->marshalItem($item),
		]);
	}

	/**
	 * @param array<string, mixed> $key
	 */
	public function deleteItem(string $table, array $key): void
	{
		$this->client->deleteItem([
			'TableName' => $table,
			'Key' => $this->marshaler->marshalItem($key),
		]);
	}

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
	): NoSqlQueryResult
	{
		$params = array_merge(
			['TableName' => $table],
			$keyCondition,
			$filter ?? [],
		);

		if ($limit !== null) {
			$params['Limit'] = $limit;
		}

		if ($exclusiveStartKey !== null) {
			$params['ExclusiveStartKey'] = $this->marshaler->marshalItem($exclusiveStartKey);
		}

		$result = $this->client->query($params);

		$items = array_map(
			function (array $item): array {
				/** @var array<string, mixed> $unmarshalled */
				$unmarshalled = $this->marshaler->unmarshalItem($item);

				return $unmarshalled;
			},
			$result['Items'] ?? [],
		);

		/** @var array<string, mixed>|null $lastEvaluatedKey */
		$lastEvaluatedKey = null;

		if (isset($result['LastEvaluatedKey'])) {
			/** @var array<string, mixed> $key */
			$key = $this->marshaler->unmarshalItem($result['LastEvaluatedKey']);
			$lastEvaluatedKey = $key;
		}

		return new NoSqlQueryResult($items, $lastEvaluatedKey);
	}

	/**
	 * Delete all items matching a key condition query, in paginated 25-item batches with retry.
	 *
	 * @param array<string, mixed> $keyCondition
	 * @param array<string> $keySchema
	 * @throws \RuntimeException When unprocessed items remain after 5 retries for any chunk.
	 */
	public function batchDeleteByQuery(string $table, array $keyCondition, array $keySchema): void
	{
		/** @var array<string, mixed>|null $exclusiveStartKey */
		$exclusiveStartKey = null;

		do {
			$queryParams = array_merge(
				['TableName' => $table],
				$keyCondition,
			);

			if ($exclusiveStartKey !== null) {
				$queryParams['ExclusiveStartKey'] = $this->marshaler->marshalItem($exclusiveStartKey);
			}

			$queryResult = $this->client->query($queryParams);

			/** @var array<int, array<string, mixed>> $rawItems */
			$rawItems = $queryResult['Items'] ?? [];

			if ($rawItems !== []) {
				/** @var array<int, array<string, mixed>> $keys */
				$keys = array_map(function (array $rawItem) use ($keySchema): array {
					/** @var array<string, mixed> $unmarshalled */
					$unmarshalled = $this->marshaler->unmarshalItem($rawItem);

					$key = [];
					foreach ($keySchema as $attr) {
						$key[$attr] = $unmarshalled[$attr];
					}

					return $key;
				}, $rawItems);

				$chunks = array_chunk($keys, 25);

				foreach ($chunks as $chunk) {
					/** @var array<int, array<string, mixed>> $typedChunk */
					$typedChunk = $chunk;
					$this->deleteBatchWithRetry($table, $typedChunk);
				}
			}

			/** @var array<string, mixed>|null $exclusiveStartKey */
			$exclusiveStartKey = null;

			if (isset($queryResult['LastEvaluatedKey'])) {
				/** @var array<string, mixed> $nextKey */
				$nextKey = $this->marshaler->unmarshalItem($queryResult['LastEvaluatedKey']);
				$exclusiveStartKey = $nextKey;
			}
		} while ($exclusiveStartKey !== null);
	}

	private const MAX_BATCH_DELETE_RETRIES = 5;

	/**
	 * Send a single batchWriteItem delete request and retry unprocessed items with
	 * exponential backoff and jitter for up to MAX_BATCH_DELETE_RETRIES attempts.
	 *
	 * @param array<int, array<string, mixed>> $keys  Unmarshalled composite keys
	 * @throws \RuntimeException When unprocessed items remain after the retry ceiling.
	 */
	private function deleteBatchWithRetry(string $table, array $keys): void
	{
		$deleteRequests = array_map(function (array $key): array {
			return ['DeleteRequest' => ['Key' => $this->marshaler->marshalItem($key)]];
		}, $keys);

		$attempt = 0;

		while ($deleteRequests !== []) {
			$result = $this->client->batchWriteItem([
				'RequestItems' => [$table => $deleteRequests],
			]);

			/** @var array<string, array<int, array<string, mixed>>> $unprocessed */
			$unprocessed = $result['UnprocessedItems'] ?? [];

			/** @var array<int, array<string, mixed>> $remaining */
			$remaining = $unprocessed[$table] ?? [];

			if ($remaining === []) {
				return;
			}

			$attempt++;

			if ($attempt > self::MAX_BATCH_DELETE_RETRIES) {
				throw new \RuntimeException(sprintf(
					'DynamoDbAdapter::batchDeleteByQuery failed: %d unprocessed items remain in table "%s" after %d retries.',
					count($remaining),
					$table,
					self::MAX_BATCH_DELETE_RETRIES,
				));
			}

			// Exponential backoff with jitter: base 100 ms * 2^attempt ± 50 ms jitter, capped at 5 s
			$baseMs = min(100 * (2 ** $attempt), 5000);
			$jitterMs = random_int(-50, 50);
			$sleepUs = max(0, ($baseMs + $jitterMs)) * 1000;
			usleep($sleepUs);

			$deleteRequests = $remaining;
		}
	}
}
