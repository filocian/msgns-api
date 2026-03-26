<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Infrastructure\Persistence\DynamoDbProductUsageAdapter;
use Src\Shared\Core\Ports\NoSqlPort;
use Src\Shared\Core\Ports\NoSqlQueryResult;

describe('DynamoDbProductUsageAdapter', function () {

    // ─── writeUsageEvent ────────────────────────────────────────────────────

    describe('writeUsageEvent', function () {
        it('puts item with correct field names and UTC timestamp', function () {
            /** @var MockInterface&NoSqlPort $noSql */
            $noSql = Mockery::mock(NoSqlPort::class);

            $noSql->shouldReceive('putItem')
                ->once()
                ->withArgs(function (string $table, array $item): bool {
                    return $table === 'product_usage_table'
                        && $item['productId'] === 42
                        && $item['userId'] === 7
                        && $item['productName'] === 'GPT-4 Pro'
                        && $item['scannedAt'] === '2024-06-15 10:30:00.000000';
                });

            $adapter = new DynamoDbProductUsageAdapter($noSql, 'product_usage_table');

            $adapter->writeUsageEvent(
                productId: 42,
                userId: 7,
                productName: 'GPT-4 Pro',
                timestamp: new DateTimeImmutable('2024-06-15 10:30:00.000000', new DateTimeZone('UTC')),
            );

            expect(true)->toBeTrue();
        });

        it('normalizes non-UTC timestamp to UTC before persisting', function () {
            /** @var MockInterface&NoSqlPort $noSql */
            $noSql = Mockery::mock(NoSqlPort::class);

            // 2024-06-15 12:30:00 +02:00 → UTC is 2024-06-15 10:30:00
            $noSql->shouldReceive('putItem')
                ->once()
                ->withArgs(function (string $table, array $item): bool {
                    return $item['scannedAt'] === '2024-06-15 10:30:00.000000';
                });

            $adapter = new DynamoDbProductUsageAdapter($noSql, 'product_usage_table');

            $adapter->writeUsageEvent(
                productId: 1,
                userId: 1,
                productName: 'Test',
                timestamp: new DateTimeImmutable('2024-06-15 12:30:00', new DateTimeZone('+02:00')),
            );
        });

        it('uses Y-m-d H:i:s.u microsecond format for the sort key', function () {
            /** @var MockInterface&NoSqlPort $noSql */
            $noSql = Mockery::mock(NoSqlPort::class);

            $noSql->shouldReceive('putItem')
                ->once()
                ->withArgs(function (string $table, array $item): bool {
                    // Format must match Y-m-d H:i:s.u — 6 decimal digits
                    return (bool) preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/', $item['scannedAt']);
                });

            $adapter = new DynamoDbProductUsageAdapter($noSql, 'product_usage_table');

            $adapter->writeUsageEvent(
                productId: 99,
                userId: 3,
                productName: 'Claude',
                timestamp: new DateTimeImmutable('2024-01-01 00:00:00', new DateTimeZone('UTC')),
            );
        });
    });

    // ─── queryProductUsage ──────────────────────────────────────────────────

    describe('queryProductUsage', function () {
        it('queries with productId and inclusive BETWEEN date range condition', function () {
            /** @var MockInterface&NoSqlPort $noSql */
            $noSql = Mockery::mock(NoSqlPort::class);

            $noSql->shouldReceive('query')
                ->once()
                ->withArgs(function (string $table, array $keyCondition): bool {
                    return $table === 'product_usage_table'
                        && str_contains($keyCondition['KeyConditionExpression'], 'productId = :pid')
                        && str_contains($keyCondition['KeyConditionExpression'], 'scannedAt BETWEEN :start AND :end')
                        && $keyCondition['ExpressionAttributeValues'][':pid'] === 42
                        && $keyCondition['ExpressionAttributeValues'][':start'] === '2024-01-01 00:00:00.000000'
                        && $keyCondition['ExpressionAttributeValues'][':end'] === '2024-01-31 23:59:59.000000';
                })
                ->andReturn(new NoSqlQueryResult([], null));

            $adapter = new DynamoDbProductUsageAdapter($noSql, 'product_usage_table');

            $result = $adapter->queryProductUsage(
                productId: 42,
                startDate: new DateTimeImmutable('2024-01-01 00:00:00', new DateTimeZone('UTC')),
                endDate: new DateTimeImmutable('2024-01-31 23:59:59', new DateTimeZone('UTC')),
            );

            expect($result)->toBeArray()->toBeEmpty();
        });

        it('returns mapped records with correct types', function () {
            /** @var MockInterface&NoSqlPort $noSql */
            $noSql = Mockery::mock(NoSqlPort::class);

            $noSql->shouldReceive('query')
                ->once()
                ->andReturn(new NoSqlQueryResult([
                    [
                        'productId'   => 42,
                        'userId'      => 7,
                        'productName' => 'GPT-4 Pro',
                        'scannedAt'   => '2024-06-15 10:30:00.000000',
                    ],
                ], null));

            $adapter = new DynamoDbProductUsageAdapter($noSql, 'product_usage_table');

            $result = $adapter->queryProductUsage(
                productId: 42,
                startDate: new DateTimeImmutable('2024-01-01', new DateTimeZone('UTC')),
                endDate: new DateTimeImmutable('2024-12-31', new DateTimeZone('UTC')),
            );

            expect($result)->toHaveCount(1)
                ->and($result[0]['productId'])->toBe(42)
                ->and($result[0]['userId'])->toBe(7)
                ->and($result[0]['productName'])->toBe('GPT-4 Pro')
                ->and($result[0]['scannedAt'])->toBe('2024-06-15 10:30:00.000000');
        });

        it('paginates through multiple pages and aggregates all results', function () {
            /** @var MockInterface&NoSqlPort $noSql */
            $noSql = Mockery::mock(NoSqlPort::class);

            // First page returns 1 item and has a continuation key
            $noSql->shouldReceive('query')
                ->once()
                ->withArgs(fn (string $table, array $kc, mixed $f, mixed $l, ?array $startKey) => $startKey === null)
                ->andReturn(new NoSqlQueryResult(
                    [
                        [
                            'productId'   => 1,
                            'userId'      => 10,
                            'productName' => 'Model A',
                            'scannedAt'   => '2024-01-01 00:00:00.000000',
                        ],
                    ],
                    ['productId' => 1, 'scannedAt' => '2024-01-01 00:00:00.000000'],
                ));

            // Second page returns 1 item and no continuation key
            $noSql->shouldReceive('query')
                ->once()
                ->withArgs(fn (string $table, array $kc, mixed $f, mixed $l, ?array $startKey) => $startKey !== null)
                ->andReturn(new NoSqlQueryResult(
                    [
                        [
                            'productId'   => 1,
                            'userId'      => 11,
                            'productName' => 'Model B',
                            'scannedAt'   => '2024-01-02 00:00:00.000000',
                        ],
                    ],
                    null,
                ));

            $adapter = new DynamoDbProductUsageAdapter($noSql, 'product_usage_table');

            $result = $adapter->queryProductUsage(
                productId: 1,
                startDate: new DateTimeImmutable('2024-01-01', new DateTimeZone('UTC')),
                endDate: new DateTimeImmutable('2024-01-31', new DateTimeZone('UTC')),
            );

            expect($result)->toHaveCount(2);
        });

        it('normalizes start/end dates to UTC before building the key condition', function () {
            /** @var MockInterface&NoSqlPort $noSql */
            $noSql = Mockery::mock(NoSqlPort::class);

            // +02:00 offsets must be converted to UTC in the expression values
            $noSql->shouldReceive('query')
                ->once()
                ->withArgs(function (string $table, array $kc): bool {
                    // 2024-01-01 02:00:00 +02:00 → UTC: 2024-01-01 00:00:00
                    // 2024-01-31 25:59:59 +02:00 → UTC: 2024-01-31 23:59:59
                    return $kc['ExpressionAttributeValues'][':start'] === '2024-01-01 00:00:00.000000'
                        && $kc['ExpressionAttributeValues'][':end'] === '2024-01-31 23:59:59.000000';
                })
                ->andReturn(new NoSqlQueryResult([], null));

            $adapter = new DynamoDbProductUsageAdapter($noSql, 'product_usage_table');

            $adapter->queryProductUsage(
                productId: 5,
                startDate: new DateTimeImmutable('2024-01-01 02:00:00', new DateTimeZone('+02:00')),
                endDate: new DateTimeImmutable('2024-02-01 01:59:59', new DateTimeZone('+02:00')),
            );

            expect(true)->toBeTrue();
        });

        it('returns empty array when no events match', function () {
            /** @var MockInterface&NoSqlPort $noSql */
            $noSql = Mockery::mock(NoSqlPort::class);

            $noSql->shouldReceive('query')
                ->once()
                ->andReturn(new NoSqlQueryResult([], null));

            $adapter = new DynamoDbProductUsageAdapter($noSql, 'product_usage_table');

            $result = $adapter->queryProductUsage(
                productId: 99,
                startDate: new DateTimeImmutable('2024-01-01', new DateTimeZone('UTC')),
                endDate: new DateTimeImmutable('2024-01-31', new DateTimeZone('UTC')),
            );

            expect($result)->toBeArray()->toBeEmpty();
        });
    });

    // ─── deleteProductUsage ─────────────────────────────────────────────────

    describe('deleteProductUsage', function () {
        it('delegates to batchDeleteByQuery with correct table, key condition and key schema', function () {
            /** @var MockInterface&NoSqlPort $noSql */
            $noSql = Mockery::mock(NoSqlPort::class);

            $noSql->shouldReceive('batchDeleteByQuery')
                ->once()
                ->withArgs(function (string $table, array $keyCondition, array $keySchema): bool {
                    return $table === 'product_usage_table'
                        && str_contains($keyCondition['KeyConditionExpression'], 'productId = :pid')
                        && $keyCondition['ExpressionAttributeValues'][':pid'] === 42
                        && $keySchema === ['productId', 'scannedAt'];
                });

            $adapter = new DynamoDbProductUsageAdapter($noSql, 'product_usage_table');
            $adapter->deleteProductUsage(42);

            expect(true)->toBeTrue();
        });

        it('propagates RuntimeException when batchDeleteByQuery fails', function () {
            /** @var MockInterface&NoSqlPort $noSql */
            $noSql = Mockery::mock(NoSqlPort::class);

            $noSql->shouldReceive('batchDeleteByQuery')
                ->once()
                ->andThrow(new RuntimeException('DynamoDbAdapter::batchDeleteByQuery failed: unprocessed items remain'));

            $adapter = new DynamoDbProductUsageAdapter($noSql, 'product_usage_table');

            expect(fn () => $adapter->deleteProductUsage(7))
                ->toThrow(RuntimeException::class, 'DynamoDbAdapter::batchDeleteByQuery failed');
        });
    });
});
