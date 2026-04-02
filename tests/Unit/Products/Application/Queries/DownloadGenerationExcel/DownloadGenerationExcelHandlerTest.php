<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Queries\DownloadGenerationExcel\DownloadGenerationExcelHandler;
use Src\Products\Application\Queries\DownloadGenerationExcel\DownloadGenerationExcelQuery;
use Src\Products\Domain\Entities\GenerationHistory;
use Src\Products\Domain\Ports\GenerationHistoryRepositoryPort;
use Src\Shared\Core\Errors\NotFound;

afterEach(fn () => Mockery::close());

describe('DownloadGenerationExcelHandler', function () {
    it('returns full GenerationHistory entity with excelBlob when record exists', function () {
        $history = GenerationHistory::create(
            generatedAt: new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
            totalCount: 5,
            summary: [],
            excelBlob: 'binary-xlsx',
            generatedById: 1,
        );

        /** @var MockInterface&GenerationHistoryRepositoryPort $repository */
        $repository = Mockery::mock(GenerationHistoryRepositoryPort::class);
        $repository->shouldReceive('findById')->once()->with(42)->andReturn($history);

        $handler = new DownloadGenerationExcelHandler($repository);
        $result = $handler->handle(new DownloadGenerationExcelQuery(generationId: 42));

        expect($result)->toBe($history)
            ->and($result->excelBlob)->toBe('binary-xlsx');
    });

    it('throws NotFound when generationId does not exist in repository', function () {
        /** @var MockInterface&GenerationHistoryRepositoryPort $repository */
        $repository = Mockery::mock(GenerationHistoryRepositoryPort::class);
        $repository->shouldReceive('findById')->once()->with(999)->andReturn(null);

        $handler = new DownloadGenerationExcelHandler($repository);

        expect(fn () => $handler->handle(new DownloadGenerationExcelQuery(generationId: 999)))
            ->toThrow(NotFound::class, 'generation_history_not_found');
    });

    it('passes the correct generationId to findById', function () {
        /** @var MockInterface&GenerationHistoryRepositoryPort $repository */
        $repository = Mockery::mock(GenerationHistoryRepositoryPort::class);
        $repository->shouldReceive('findById')->once()->with(7)->andReturn(GenerationHistory::create(
            generatedAt: new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
            totalCount: 1,
            summary: [],
            excelBlob: 'xlsx',
            generatedById: null,
        ));

        $handler = new DownloadGenerationExcelHandler($repository);
        $handler->handle(new DownloadGenerationExcelQuery(generationId: 7));

        expect(true)->toBeTrue();
    });
});
