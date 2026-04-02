<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Products\Domain\DataTransfer\GenerationHistorySummaryItem;
use Src\Products\Domain\Entities\GenerationHistory;
use Src\Products\Infrastructure\Persistence\EloquentGenerationHistoryRepository;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('generation_history')) {
        Schema::create('generation_history', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('generated_at');
            $table->unsignedInteger('total_count');
            $table->json('summary');
            $table->binary('excel_blob');
            $table->foreignId('generated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
});

function makeGenerationHistory(int $totalCount = 5, ?int $generatedById = null, ?DateTimeImmutable $generatedAt = null): GenerationHistory
{
    return GenerationHistory::create(
        generatedAt: $generatedAt ?? new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
        totalCount: $totalCount,
        summary: [new GenerationHistorySummaryItem('QR_BASIC', 'QR Basic', 5, null, null)],
        excelBlob: 'xlsx-binary',
        generatedById: $generatedById,
    );
}

describe('EloquentGenerationHistoryRepository', function () {
    it('saves a GenerationHistory entity and row appears in DB', function () {
        $repository = app(EloquentGenerationHistoryRepository::class);
        $history = makeGenerationHistory(totalCount: 5);

        $repository->save($history);

        $this->assertDatabaseCount('generation_history', 1);
        $this->assertDatabaseHas('generation_history', ['total_count' => 5]);
    });

    it('finds a GenerationHistory by ID and includes excel_blob in returned entity', function () {
        $repository = app(EloquentGenerationHistoryRepository::class);
        $repository->save(makeGenerationHistory(totalCount: 8));
        $id = (int) DB::table('generation_history')->value('id');

        $history = $repository->findById($id);

        expect($history)->not->toBeNull()
            ->and($history?->id)->toBe($id)
            ->and($history?->excelBlob)->toBe('xlsx-binary');
    });

    it('returns null for non existent ID', function () {
        $repository = app(EloquentGenerationHistoryRepository::class);

        expect($repository->findById(999))->toBeNull();
    });

    it('lists paginated history ordered by generated_at desc and excludes excel_blob', function () {
        $repository = app(EloquentGenerationHistoryRepository::class);
        $repository->save(makeGenerationHistory(generatedAt: new DateTimeImmutable('2026-04-02T12:30:00+00:00')));
        $repository->save(makeGenerationHistory(generatedAt: new DateTimeImmutable('2026-04-02T13:30:00+00:00')));

        $result = $repository->listPaginated(1, 15);

        expect($result->items)->toHaveCount(2)
            ->and($result->items[0]['id'])->toBeGreaterThan($result->items[1]['id'])
            ->and($result->items[0])->not->toHaveKey('excel_blob');
    });

    it('eager loads user email in paginated list', function () {
        $user = $this->create_user(['email' => 'history@example.com']);
        $repository = app(EloquentGenerationHistoryRepository::class);
        $repository->save(makeGenerationHistory(generatedById: $user->id));

        $result = $repository->listPaginated(1, 15);

        expect($result->items[0]['generated_by'])->toBe([
            'id' => $user->id,
            'email' => 'history@example.com',
        ]);
    });

    it('returns null for generated_by when user was deleted', function () {
        $user = $this->create_user(['email' => 'gone@example.com']);
        $repository = app(EloquentGenerationHistoryRepository::class);
        $repository->save(makeGenerationHistory(generatedById: $user->id));
        $user->delete();

        $result = $repository->listPaginated(1, 15);

        expect($result->items[0]['generated_by'])->toBeNull();
    });

    it('returns correct pagination metadata', function () {
        $repository = app(EloquentGenerationHistoryRepository::class);

        foreach (range(1, 3) as $index) {
            $repository->save(makeGenerationHistory(
                totalCount: $index,
                generatedAt: new DateTimeImmutable(sprintf('2026-04-02T1%d:30:00+00:00', $index)),
            ));
        }

        $result = $repository->listPaginated(2, 2);

        expect($result->currentPage)->toBe(2)
            ->and($result->perPage)->toBe(2)
            ->and($result->total)->toBe(3)
            ->and($result->lastPage)->toBe(2)
            ->and($result->items)->toHaveCount(1);
    });
});
