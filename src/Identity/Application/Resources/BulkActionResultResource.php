<?php

declare(strict_types=1);

namespace Src\Identity\Application\Resources;

/**
 * Shared response DTO for all bulk operations.
 * Matches frontend contract with summary + results array.
 */
final readonly class BulkActionResultResource
{
    /**
     * @param array<int, BulkUserResult> $results
     */
    public function __construct(
        public BulkSummary $summary,
        public array $results,
    ) {}

    /**
     * Convert to array for API response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary->toArray(),
            'results' => array_map(
                fn(BulkUserResult $r) => $r->toArray(),
                $this->results
            ),
        ];
    }
}
