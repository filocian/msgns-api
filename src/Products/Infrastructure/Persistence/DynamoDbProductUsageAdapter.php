<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Shared\Core\Ports\NoSqlPort;

/**
 * DynamoDB-backed implementation of ProductUsagePort.
 *
 * DynamoDB table schema:
 *   productId (N)  — partition key
 *   scannedAt (S)  — sort key, UTC format "Y-m-d H:i:s.u"
 *   userId    (N)
 *   productName (S)
 */
final class DynamoDbProductUsageAdapter implements ProductUsagePort
{
    public function __construct(
        private readonly NoSqlPort $noSql,
        private readonly string $table,
    ) {}

    /**
     * Persist one usage event to DynamoDB.
     * The timestamp is normalized to UTC "Y-m-d H:i:s.u" before storage.
     */
    public function writeUsageEvent(
        int $productId,
        int $userId,
        string $productName,
        \DateTimeImmutable $timestamp,
    ): void {
        $utc = $timestamp->setTimezone(new \DateTimeZone('UTC'));

        $this->noSql->putItem($this->table, [
            'productId'   => $productId,
            'scannedAt'   => $utc->format('Y-m-d H:i:s.u'),
            'userId'      => $userId,
            'productName' => $productName,
        ]);
    }

    /**
     * Query all usage events for a product within an inclusive UTC date range.
     *
     * Both $startDate and $endDate are normalized to UTC "Y-m-d H:i:s.u" for the
     * BETWEEN key condition on the sort key.
     *
     * @return array<int, array{productId: int, userId: int, productName: string, scannedAt: string}>
     */
    public function queryProductUsage(
        int $productId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): array {
        $startUtc = $startDate->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
        $endUtc   = $endDate->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');

        $keyCondition = [
            'KeyConditionExpression'    => 'productId = :pid AND scannedAt BETWEEN :start AND :end',
            'ExpressionAttributeValues' => [
                ':pid'   => $productId,
                ':start' => $startUtc,
                ':end'   => $endUtc,
            ],
        ];

        /** @var array<int, array{productId: int, userId: int, productName: string, scannedAt: string}> $results */
        $results = [];

        /** @var array<string, mixed>|null $lastKey */
        $lastKey = null;

        do {
            $page    = $this->noSql->query($this->table, $keyCondition, null, null, $lastKey);
            $lastKey = $page->lastEvaluatedKey;

            foreach ($page->items as $item) {
                $results[] = [
                    'productId'   => (int) $item['productId'],
                    'userId'      => (int) $item['userId'],
                    'productName' => (string) $item['productName'],
                    'scannedAt'   => (string) $item['scannedAt'],
                ];
            }
        } while ($lastKey !== null);

        return $results;
    }

    /**
     * Delete all usage events for a product via paginated batch deletion.
     *
     * Delegates to NoSqlPort::batchDeleteByQuery() which handles chunking,
     * retry with exponential backoff + jitter, and throws RuntimeException
     * if the retry ceiling (5 attempts) is exceeded for any chunk.
     */
    public function deleteProductUsage(int $productId): void
    {
        $this->noSql->batchDeleteByQuery(
            $this->table,
            [
                'KeyConditionExpression'    => 'productId = :pid',
                'ExpressionAttributeValues' => [
                    ':pid' => $productId,
                ],
            ],
            ['productId', 'scannedAt'],
        );
    }
}
