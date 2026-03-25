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
    /**
     * @param array<string, mixed> $data
     */
    public function writeUsage(int $productId, array $data): void
    {
        // No-op: Will be implemented in issue #13 with DynamoDB
    }

    /**
     * @return array<string, mixed>
     */
    public function queryUsage(int $productId): array
    {
        // No-op: Will be implemented in issue #13 with DynamoDB
        return [];
    }

    public function deleteUsage(int $productId): void
    {
        // No-op: Will be implemented in issue #13 with DynamoDB
    }
}
