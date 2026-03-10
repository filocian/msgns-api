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
});
