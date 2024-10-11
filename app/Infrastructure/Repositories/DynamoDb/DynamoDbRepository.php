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
}
