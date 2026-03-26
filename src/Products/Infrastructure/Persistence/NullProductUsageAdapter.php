<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Src\Products\Domain\Ports\ProductUsagePort;

/**
 * Null implementation of ProductUsagePort.
 * This is a placeholder that will be replaced by the actual DynamoDB
 * implementation in issue #13.
 *
 * All methods are no-ops.
 */
final class NullProductUsageAdapter implements ProductUsagePort
{
    public function writeUsageEvent(int $productId, int $userId, string $productName, \DateTimeImmutable $timestamp): void
    {
        // No-op: Will be implemented in issue #13 with DynamoDB
    }

    /**
     * @return array<int, array{productId: int, userId: int, productName: string, scannedAt: string}>
     */
    public function queryProductUsage(int $productId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        // No-op: Will be implemented in issue #13 with DynamoDB
        return [];
    }

    public function deleteProductUsage(int $productId): void
    {
        // No-op: Will be implemented in issue #13 with DynamoDB
    }
}
