<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

interface ProductUsagePort
{
    /**
     * @param array<string, mixed> $data
     */
    public function writeUsage(int $productId, array $data): void;

    /**
     * @return array<string, mixed>
     */
    public function queryUsage(int $productId): array;

    public function deleteUsage(int $productId): void;
}
