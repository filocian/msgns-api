<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListGenerationHistory;

use Src\Shared\Core\Bus\Query;

final readonly class ListGenerationHistoryQuery implements Query
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
        public string $timezone = 'UTC',
    ) {}

    public function queryName(): string
    {
        return 'products.list_generation_history';
    }
}
