<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Src\Products\Domain\Entities\GenerationHistory;
use Src\Products\Domain\Ports\GenerationHistoryRepositoryPort;
use Src\Shared\Core\Bus\PaginatedResult;

final class EloquentGenerationHistoryRepository implements GenerationHistoryRepositoryPort
{
    public function save(GenerationHistory $history): void
    {
        $model = new EloquentGenerationHistory();
        $model->fill([
            'generated_at' => $history->generatedAt->format('Y-m-d H:i:s'),
            'total_count' => $history->totalCount,
            'summary' => $history->summaryToArray(),
            'excel_blob' => $history->excelBlob,
            'generated_by_id' => $history->generatedById,
        ]);
        $model->save();
    }

    public function findById(int $id): ?GenerationHistory
    {
        $model = EloquentGenerationHistory::query()->find($id);

        if ($model === null) {
            return null;
        }

        return $model->toDomainEntity();
    }

    public function listPaginated(int $page, int $perPage): PaginatedResult
    {
        $paginator = EloquentGenerationHistory::query()
            ->select(['id', 'generated_at', 'total_count', 'summary', 'generated_by_id', 'created_at', 'updated_at'])
            ->with('generatedBy:id,email')
            ->orderByDesc('generated_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(static function (EloquentGenerationHistory $history): array {
            /** @var array{id: int, email: string}|null $generatedBy */
            $generatedBy = $history->generatedBy !== null
                ? ['id' => $history->generatedBy->id, 'email' => $history->generatedBy->email]
                : null;

            return [
                'id' => $history->id,
                'generated_at' => $history->generated_at,
                'total_count' => $history->total_count,
                'summary' => $history->summary,
                'generated_by' => $generatedBy,
            ];
        })->values()->all();

        return new PaginatedResult(
            items: $items,
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }
}
