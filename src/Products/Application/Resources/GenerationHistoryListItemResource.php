<?php

declare(strict_types=1);

namespace Src\Products\Application\Resources;

final readonly class GenerationHistoryListItemResource
{
    /**
     * @param list<array{type_code: string, type_name: string, quantity: int, size: ?string, description: ?string}> $summary
     * @param array{id: int, email: string}|null $generatedBy
     */
    public function __construct(
        public int $id,
        public string $generatedAt,
        public int $totalCount,
        public array $summary,
        public ?array $generatedBy,
    ) {}

    /**
     * @return array{
     *   id: int,
     *   generated_at: string,
     *   total_count: int,
     *   summary: list<array{type_code: string, type_name: string, quantity: int, size: ?string, description: ?string}>,
     *   generated_by: array{id: int, email: string}|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'generated_at' => $this->generatedAt,
            'total_count' => $this->totalCount,
            'summary' => $this->summary,
            'generated_by' => $this->generatedBy,
        ];
    }
}
