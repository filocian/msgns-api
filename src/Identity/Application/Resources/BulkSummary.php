<?php

declare(strict_types=1);

namespace Src\Identity\Application\Resources;

/**
 * Summary counts for bulk operation results.
 * Uses spec-authoritative field names: requested, succeeded, failed.
 */
final readonly class BulkSummary
{
    public function __construct(
        public int $requested,
        public int $succeeded,
        public int $failed,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'requested' => $this->requested,
            'succeeded' => $this->succeeded,
            'failed' => $this->failed,
        ];
    }
}
