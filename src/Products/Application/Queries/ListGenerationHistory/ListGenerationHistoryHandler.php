<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListGenerationHistory;

use Carbon\Carbon;
use DateTimeImmutable;
use Src\Products\Application\Resources\GenerationHistoryListItemResource;
use Src\Products\Domain\Ports\GenerationHistoryRepositoryPort;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Shared\Core\Helpers\DateTimeConverter;

final class ListGenerationHistoryHandler implements QueryHandler
{
    public function __construct(
        private readonly GenerationHistoryRepositoryPort $repo,
    ) {}

    public function handle(Query $query): PaginatedResult
    {
        assert($query instanceof ListGenerationHistoryQuery);

        $paginated = $this->repo->listPaginated($query->page, $query->perPage);

        return new PaginatedResult(
            items: array_map(function (array $item) use ($query): array {
                $generatedAt = $item['generated_at'];

                if (! $generatedAt instanceof DateTimeImmutable && ! $generatedAt instanceof Carbon) {
                    throw new \InvalidArgumentException('The generated_at value must be a Carbon or DateTimeImmutable instance.');
                }

                $converted = DateTimeConverter::convert($generatedAt, $query->timezone);

                return (new GenerationHistoryListItemResource(
                    id: (int) $item['id'],
                    generatedAt: $converted->format('c'),
                    totalCount: (int) $item['total_count'],
                    summary: $item['summary'],
                    generatedBy: $item['generated_by'],
                ))->toArray();
            }, $paginated->items),
            currentPage: $paginated->currentPage,
            perPage: $paginated->perPage,
            total: $paginated->total,
            lastPage: $paginated->lastPage,
            overview: $paginated->overview,
        );
    }
}
