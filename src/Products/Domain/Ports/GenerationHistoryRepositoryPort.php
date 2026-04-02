<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

use Src\Products\Domain\Entities\GenerationHistory;
use Src\Shared\Core\Bus\PaginatedResult;

interface GenerationHistoryRepositoryPort
{
    public function save(GenerationHistory $history): void;

    public function findById(int $id): ?GenerationHistory;

    public function listPaginated(int $page, int $perPage): PaginatedResult;
}
