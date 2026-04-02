<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\DownloadGenerationExcel;

use Src\Products\Domain\Entities\GenerationHistory;
use Src\Products\Domain\Ports\GenerationHistoryRepositoryPort;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Shared\Core\Errors\NotFound;

final class DownloadGenerationExcelHandler implements QueryHandler
{
    public function __construct(
        private readonly GenerationHistoryRepositoryPort $repo,
    ) {}

    public function handle(Query $query): GenerationHistory
    {
        assert($query instanceof DownloadGenerationExcelQuery);

        $history = $this->repo->findById($query->generationId);

        if ($history === null) {
            throw NotFound::entity('generation_history', (string) $query->generationId);
        }

        return $history;
    }
}
