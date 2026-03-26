<?php

declare(strict_types=1);

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\Result;
use Src\Shared\Core\Ports\NoSqlQueryResult;
use Src\Shared\Infrastructure\NoSql\DynamoDbAdapter;

describe('DynamoDbAdapter', function () {
	it('puts and retrieves an item', function () {
		$marshaler = new Marshaler();
		$client = \Mockery::mock(DynamoDbClient::class);
		$client->shouldReceive('putItem')->once()->andReturn(new Result([]));
		$client->shouldReceive('getItem')->once()->andReturn(new Result([
			'Item' => $marshaler->marshalItem(['pk' => 'user#1', 'name' => 'John']),
		]));

		$adapter = new DynamoDbAdapter($client);
		$adapter->putItem('users', ['pk' => 'user#1', 'name' => 'John']);

		expect($adapter->getItem('users', ['pk' => 'user#1']))->toBe([
			'pk' => 'user#1',
			'name' => 'John',
		]);
	});

	it('deletes an item', function () {
		$client = \Mockery::mock(DynamoDbClient::class);
		$client->shouldReceive('deleteItem')->once()->andReturn(new Result([]));

		$adapter = new DynamoDbAdapter($client);
		$adapter->deleteItem('users', ['pk' => 'user#1']);

		expect(true)->toBeTrue();
	});

	it('returns null for non-existent key', function () {
		$client = \Mockery::mock(DynamoDbClient::class);
		$client->shouldReceive('getItem')->once()->andReturn(new Result([]));

		$adapter = new DynamoDbAdapter($client);

		expect($adapter->getItem('users', ['pk' => 'missing']))->toBeNull();
	});

	it('deletes non-existent key without error', function () {
		$client = \Mockery::mock(DynamoDbClient::class);
		$client->shouldReceive('deleteItem')->once()->andReturn(new Result([]));

		$adapter = new DynamoDbAdapter($client);
		$adapter->deleteItem('users', ['pk' => 'missing']);

		expect(true)->toBeTrue();
	});

	it('returns items with pagination metadata when querying', function () {
		$marshaler = new Marshaler();
		$client = \Mockery::mock(DynamoDbClient::class);
		$client->shouldReceive('query')->once()->withArgs(function (array $params) use ($marshaler) {
			return $params['TableName'] === 'users'
				&& $params['Limit'] === 5
				&& $marshaler->unmarshalItem($params['ExclusiveStartKey']) === ['pk' => 'user#1'];
		})->andReturn(new Result([
			'Items' => [
				$marshaler->marshalItem(['pk' => 'user#2', 'name' => 'Jane']),
			],
			'LastEvaluatedKey' => $marshaler->marshalItem(['pk' => 'user#2']),
		]));

		$adapter = new DynamoDbAdapter($client);
		$result = $adapter->query('users', ['KeyConditionExpression' => 'pk = :pk'], null, 5, ['pk' => 'user#1']);

		expect($result)->toBeInstanceOf(NoSqlQueryResult::class)
			->and($result->items)->toBe([
				['pk' => 'user#2', 'name' => 'Jane'],
			])
			->and($result->lastEvaluatedKey)->toBe(['pk' => 'user#2'])
			->and($result->hasMoreResults())->toBeTrue();
	});

	describe('batchDeleteByQuery', function () {
		it('deletes all items for a single page in one batch', function () {
			$marshaler = new Marshaler();
			$client = \Mockery::mock(DynamoDbClient::class);

			$items = [
				$marshaler->marshalItem(['productId' => 42, 'scannedAt' => '2024-01-01 00:00:00.000000']),
				$marshaler->marshalItem(['productId' => 42, 'scannedAt' => '2024-01-02 00:00:00.000000']),
			];

			// First query: returns 2 items, no more pages
			$client->shouldReceive('query')->once()->andReturn(new Result([
				'Items' => $items,
			]));

			// batchWriteItem called once with 2 delete requests, all processed
			$client->shouldReceive('batchWriteItem')->once()->withArgs(function (array $params) use ($marshaler) {
				$requests = $params['RequestItems']['product_usage'] ?? [];
				if (count($requests) !== 2) {
					return false;
				}
				foreach ($requests as $req) {
					if (!isset($req['DeleteRequest']['Key'])) {
						return false;
					}
				}
				return true;
			})->andReturn(new Result([
				'UnprocessedItems' => [],
			]));

			$adapter = new DynamoDbAdapter($client);
			$adapter->batchDeleteByQuery(
				'product_usage',
				['KeyConditionExpression' => 'productId = :pid', 'ExpressionAttributeValues' => [':pid' => ['N' => '42']]],
				['productId', 'scannedAt'],
			);

			expect(true)->toBeTrue(); // no exception = success
		});

		it('does nothing when query returns no items', function () {
			$client = \Mockery::mock(DynamoDbClient::class);

			// Query returns empty result set, no more pages
			$client->shouldReceive('query')->once()->andReturn(new Result([
				'Items' => [],
			]));

			// batchWriteItem must NOT be called
			$client->shouldNotReceive('batchWriteItem');

			$adapter = new DynamoDbAdapter($client);
			$adapter->batchDeleteByQuery(
				'product_usage',
				['KeyConditionExpression' => 'productId = :pid', 'ExpressionAttributeValues' => [':pid' => ['N' => '99']]],
				['productId', 'scannedAt'],
			);

			expect(true)->toBeTrue();
		});

		it('paginates through multiple query pages and deletes all', function () {
			$marshaler = new Marshaler();
			$client = \Mockery::mock(DynamoDbClient::class);

			$page1Item = $marshaler->marshalItem(['productId' => 1, 'scannedAt' => '2024-01-01 00:00:00.000000']);
			$page2Item = $marshaler->marshalItem(['productId' => 1, 'scannedAt' => '2024-01-02 00:00:00.000000']);
			$lastKeyMarshalled = $marshaler->marshalItem(['productId' => 1, 'scannedAt' => '2024-01-01 00:00:00.000000']);

			// First query page: 1 item, has LastEvaluatedKey
			$client->shouldReceive('query')->once()->withArgs(function (array $params): bool {
				return $params['TableName'] === 'product_usage' && !isset($params['ExclusiveStartKey']);
			})->andReturn(new Result([
				'Items' => [$page1Item],
				'LastEvaluatedKey' => $lastKeyMarshalled,
			]));

			// Second query page: 1 item, no more pages
			$client->shouldReceive('query')->once()->withArgs(function (array $params): bool {
				return $params['TableName'] === 'product_usage' && isset($params['ExclusiveStartKey']);
			})->andReturn(new Result([
				'Items' => [$page2Item],
			]));

			// Two batchWriteItem calls (one per page), both succeed
			$client->shouldReceive('batchWriteItem')->twice()->andReturn(new Result([
				'UnprocessedItems' => [],
			]));

			$adapter = new DynamoDbAdapter($client);
			$adapter->batchDeleteByQuery(
				'product_usage',
				['KeyConditionExpression' => 'productId = :pid', 'ExpressionAttributeValues' => [':pid' => ['N' => '1']]],
				['productId', 'scannedAt'],
			);

			expect(true)->toBeTrue();
		});

		it('retries unprocessed items and succeeds before retry ceiling', function () {
			$marshaler = new Marshaler();
			$client = \Mockery::mock(DynamoDbClient::class);

			$marshalledItem = $marshaler->marshalItem(['productId' => 5, 'scannedAt' => '2024-06-01 12:00:00.000000']);
			$deleteRequest = ['DeleteRequest' => ['Key' => $marshaler->marshalItem(['productId' => 5, 'scannedAt' => '2024-06-01 12:00:00.000000'])]];

			// Single query page with 1 item
			$client->shouldReceive('query')->once()->andReturn(new Result([
				'Items' => [$marshalledItem],
			]));

			// First batchWriteItem: item is unprocessed
			$client->shouldReceive('batchWriteItem')->once()->andReturn(new Result([
				'UnprocessedItems' => ['product_usage' => [$deleteRequest]],
			]));

			// Second batchWriteItem (retry): all processed
			$client->shouldReceive('batchWriteItem')->once()->andReturn(new Result([
				'UnprocessedItems' => [],
			]));

			$adapter = new DynamoDbAdapter($client);
			$adapter->batchDeleteByQuery(
				'product_usage',
				['KeyConditionExpression' => 'productId = :pid', 'ExpressionAttributeValues' => [':pid' => ['N' => '5']]],
				['productId', 'scannedAt'],
			);

			expect(true)->toBeTrue();
		});

		it('splits more than 25 items into multiple batchWriteItem calls', function () {
			$marshaler = new Marshaler();
			$client = \Mockery::mock(DynamoDbClient::class);

			// Build 26 items — should produce 2 chunks (25 + 1)
			$items = [];
			for ($i = 1; $i <= 26; $i++) {
				$items[] = $marshaler->marshalItem(['productId' => 10, 'scannedAt' => "2024-01-{$i} 00:00:00.000000"]);
			}

			// Single query page with 26 items, no more pages
			$client->shouldReceive('query')->once()->andReturn(new Result([
				'Items' => $items,
			]));

			// Expect exactly 2 batchWriteItem calls: first chunk of 25, second chunk of 1
			$callCount = 0;
			$client->shouldReceive('batchWriteItem')->twice()->withArgs(function (array $params) use (&$callCount): bool {
				$callCount++;
				$requests = $params['RequestItems']['product_usage'] ?? [];
				if ($callCount === 1) {
					return count($requests) === 25;
				}
				// second call: 1 remaining item
				return count($requests) === 1;
			})->andReturn(new Result([
				'UnprocessedItems' => [],
			]));

			$adapter = new DynamoDbAdapter($client);
			$adapter->batchDeleteByQuery(
				'product_usage',
				['KeyConditionExpression' => 'productId = :pid', 'ExpressionAttributeValues' => [':pid' => ['N' => '10']]],
				['productId', 'scannedAt'],
			);

			expect($callCount)->toBe(2);
		});

		it('throws RuntimeException when retry ceiling is exceeded', function () {
			$marshaler = new Marshaler();
			$client = \Mockery::mock(DynamoDbClient::class);

			$marshalledItem = $marshaler->marshalItem(['productId' => 7, 'scannedAt' => '2024-06-01 00:00:00.000000']);
			$deleteRequest = ['DeleteRequest' => ['Key' => $marshaler->marshalItem(['productId' => 7, 'scannedAt' => '2024-06-01 00:00:00.000000'])]];

			// Single query page with 1 item
			$client->shouldReceive('query')->once()->andReturn(new Result([
				'Items' => [$marshalledItem],
			]));

			// All 6 batchWriteItem calls return the same unprocessed item (1 initial + 5 retries)
			$client->shouldReceive('batchWriteItem')->times(6)->andReturn(new Result([
				'UnprocessedItems' => ['product_usage' => [$deleteRequest]],
			]));

			$adapter = new DynamoDbAdapter($client);

			expect(fn () => $adapter->batchDeleteByQuery(
				'product_usage',
				['KeyConditionExpression' => 'productId = :pid', 'ExpressionAttributeValues' => [':pid' => ['N' => '7']]],
				['productId', 'scannedAt'],
			))->toThrow(
				\RuntimeException::class,
				'DynamoDbAdapter::batchDeleteByQuery failed',
			);
		});
	});
});
