<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\DynamoDb;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Exception;
use Illuminate\Support\Facades\Log;

final class DynamoDbRepository
{
	public DynamoDbClient $client;

	/**
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->client = new DynamoDbClient([
			'region' => config('services.dynamodb.region'),
			'version' => 'latest',
			'credentials' => [
				'key' => config('services.dynamodb.key'),
				'secret' => config('services.dynamodb.secret'),
			],
			'retries' => 0,
		]);
	}

	public function listTables(): mixed
	{
		return $this->client->listTables()->get('TableNames');
	}

	public function putItem(string $tableName, array $data): void
	{
		$item = [
			'TableName' => $tableName,
			'Item' => $data,
		];

		try {
			$this->client->putItem($item);
		} catch (DynamoDbException $e) {
			Log::error($e->getMessage());
			throw $e;
		}
	}

	public function query(
		string $tableName,
		string $keyConditionExpression,
		array $expressionAttributeNames,
		array $expressionAttributeValues
	): ?\Aws\Result {
		try {
			return $this->client->query([
				'TableName' => $tableName,
				'KeyConditionExpression' => $keyConditionExpression,
				'ExpressionAttributeNames' => $expressionAttributeNames,
				'ExpressionAttributeValues' => $expressionAttributeValues,
			]);
		} catch (DynamoDbException $e) {
			Log::error($e->getMessage());
		}

		return null;
	}

	public function scan(
		string $tableName,
		string $filterExpression,
		array $expressionAttributeNames,
		array $expressionAttributeValues
	): ?\Aws\Result {
		try {
			return $this->client->scan([
				'TableName' => $tableName,
				'FilterExpression' => $filterExpression,
				'ExpressionAttributeNames' => $expressionAttributeNames,
				'ExpressionAttributeValues' => $expressionAttributeValues,
			]);
		} catch (DynamoDbException $e) {
			Log::error($e->getMessage());
		}

		return null;
	}

	/**
	 * @throws Exception
	 */
	public function deleteItem(string $tableName, string $keyName, string $keyType, string $keyValue): void
	{
		try {
			$result = $this->client->deleteItem([
				'TableName' => $tableName,
				'Key' => [
					$keyName => [
						$keyType => $keyValue,
					],
				],
			]);
		} catch (DynamoDbException $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Delete items of DynamoDB table, based on key condition
	 *
	 * @param string $tableName DynamoDB table name.
	 * @param string $keyConditionExpression condition expression.
	 * @param array $expressionAttributeValues attribute values for the given expression.
	 * @param array $keyNames array of names which compounds the key.
	 * @param array|null $expressionAttributeNames attribute names for the given expression.
	 *
	 * @throws DynamoDbException
	 *
	 * @return void
	 */
	public function batchDelete(
		string $tableName,
		string $keyConditionExpression,
		array $expressionAttributeValues,
		array $keyNames,
		array|null $expressionAttributeNames = null
	): void {
		$exclusiveStartKey = null;
		$maxRetries = 5;
		$sleepBase = 1;
		$maxSleepTime = 5;

		do {
			$params = [
				'TableName' => $tableName,
				'KeyConditionExpression' => $keyConditionExpression,
				'ExpressionAttributeValues' => $expressionAttributeValues,
			];

			if ($expressionAttributeNames !== null) {
				$params['ExpressionAttributeNames'] = $expressionAttributeNames;
			}

			if ($exclusiveStartKey !== null) {
				$params['ExclusiveStartKey'] = $exclusiveStartKey;
			}

			try {
				$result = $this->client->query($params);
				$items = $result['Items'] ?? [];
				$exclusiveStartKey = $result['LastEvaluatedKey'] ?? null;

				if (empty($items)) {
					continue;
				}

				$deleteRequests = array_map(function ($item) use ($keyNames) {
					$key = [];
					foreach ($keyNames as $keyName) {
						if (isset($item[$keyName])) {
							$key[$keyName] = $item[$keyName];
						} else {
							Log::warning("Key '{$keyName}' not found.");
						}
					}
					return ['DeleteRequest' => ['Key' => $key]];
				}, $items);

				// Max of 25 items in a batch according to DynamoDB limits
				$chunks = array_chunk($deleteRequests, 25);

				foreach ($chunks as $chunk) {
					$batchParams = [
						'RequestItems' => [
							$tableName => $chunk,
						],
					];

					$retries = 0;
					do {
						try {
							$batchResult = $this->client->batchWriteItem($batchParams);

							if (!empty($batchResult['UnprocessedItems'])) {
								$batchParams['RequestItems'] = $batchResult['UnprocessedItems'];
								$retries++;

								// exponential backoff with jitter
								$maxDelay = min($sleepBase * (2 ** $retries), $maxSleepTime);
								$sleepTime = $maxDelay / 2 + rand(0, $maxDelay * 1000) / 1000 / 2;
								usleep((int) ($sleepTime * 1e6));
							} else {
								break; // All items processed
							}
						} catch (DynamoDbException $e) {
							Log::error('Error deleting item batch: ' . $e->getMessage());
							$retries++;

							// exponential backoff with jitter
							$maxDelay = min($sleepBase * (2 ** $retries), $maxSleepTime);
							$sleepTime = $maxDelay / 2 + rand(0, $maxDelay * 1000) / 1000 / 2;
							usleep((int) ($sleepTime * 1e6));
						}
					} while ($retries < $maxRetries);

					if ($retries === $maxRetries) {
						Log::warning('Some items could not be deleted.');
					}
				}
			} catch (DynamoDbException $e) {
				Log::error('Error querying items: ' . $e->getMessage());
				throw $e;
			}
		} while ($exclusiveStartKey !== null);
	}
}
