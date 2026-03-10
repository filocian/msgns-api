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
}
