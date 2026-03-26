<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

interface ProductUsagePort
{
    public function writeUsageEvent(int $productId, int $userId, string $productName, \DateTimeImmutable $timestamp): void;

    /**
     * @return array<int, array{productId: int, userId: int, productName: string, scannedAt: string}>
     */
    public function queryProductUsage(int $productId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array;

    public function deleteProductUsage(int $productId): void;
}
