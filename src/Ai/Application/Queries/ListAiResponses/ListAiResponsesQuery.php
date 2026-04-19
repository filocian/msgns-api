<?php

declare(strict_types=1);

namespace Src\Ai\Application\Queries\ListAiResponses;

use Src\Shared\Core\Bus\Query;

final readonly class ListAiResponsesQuery implements Query
{
    public function __construct(
        public int $userId,
        public int $page = 1,
        public int $perPage = 15,
        public ?string $status = null,
        public ?string $productType = null,
    ) {}

    public function queryName(): string
    {
        return 'ai.list_ai_responses';
    }
}
