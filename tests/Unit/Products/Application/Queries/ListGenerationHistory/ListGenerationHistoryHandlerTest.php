<?php

declare(strict_types=1);

use Carbon\Carbon;
use Mockery\Expectation;
use Mockery\MockInterface;
use Src\Products\Application\Queries\ListGenerationHistory\ListGenerationHistoryHandler;
use Src\Products\Application\Queries\ListGenerationHistory\ListGenerationHistoryQuery;
use Src\Products\Domain\Ports\GenerationHistoryRepositoryPort;
use Src\Shared\Core\Bus\PaginatedResult;

afterEach(fn () => Mockery::close());

describe('ListGenerationHistoryHandler', function () {
    it('delegates to repository with correct page and perPage', function () {
        $expected = new PaginatedResult(items: [], currentPage: 2, perPage: 10, total: 0, lastPage: 1);

        /** @var MockInterface&GenerationHistoryRepositoryPort $repository */
        $repository = Mockery::mock(GenerationHistoryRepositoryPort::class);
        /** @var Expectation $expectation */
        $expectation = $repository->shouldReceive('listPaginated');
        $expectation->once()->with(2, 10)->andReturn($expected);

        $handler = new ListGenerationHistoryHandler($repository);
        $result = $handler->handle(new ListGenerationHistoryQuery(page: 2, perPage: 10, timezone: 'UTC'));

        expect($result->currentPage)->toBe(2)
            ->and($result->perPage)->toBe(10);
    });

    it('converts generated_at to requested timezone', function () {
        /** @var MockInterface&GenerationHistoryRepositoryPort $repository */
        $repository = Mockery::mock(GenerationHistoryRepositoryPort::class);
        $repository->shouldReceive('listPaginated')->once()->andReturn(new PaginatedResult(
            items: [[
                'id' => 42,
                'generated_at' => Carbon::parse('2026-04-02T12:30:00+00:00'),
                'total_count' => 15,
                'summary' => [],
                'generated_by' => ['id' => 7, 'email' => 'admin@example.com'],
            ]],
            currentPage: 1,
            perPage: 15,
            total: 1,
            lastPage: 1,
        ));

        $handler = new ListGenerationHistoryHandler($repository);
        $result = $handler->handle(new ListGenerationHistoryQuery(timezone: 'Europe/Madrid'));

        expect($result->items[0]['generated_at'])->toBe('2026-04-02T14:30:00+02:00');
    });

    it('defaults to UTC when timezone is UTC', function () {
        /** @var MockInterface&GenerationHistoryRepositoryPort $repository */
        $repository = Mockery::mock(GenerationHistoryRepositoryPort::class);
        $repository->shouldReceive('listPaginated')->once()->andReturn(new PaginatedResult(
            items: [[
                'id' => 1,
                'generated_at' => new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
                'total_count' => 1,
                'summary' => [],
                'generated_by' => null,
            ]],
            currentPage: 1,
            perPage: 15,
            total: 1,
            lastPage: 1,
        ));

        $handler = new ListGenerationHistoryHandler($repository);
        $result = $handler->handle(new ListGenerationHistoryQuery());

        expect($result->items[0]['generated_at'])->toBe('2026-04-02T12:30:00+00:00');
    });

    it('builds resource arrays for each paginated item and preserves pagination metadata', function () {
        /** @var MockInterface&GenerationHistoryRepositoryPort $repository */
        $repository = Mockery::mock(GenerationHistoryRepositoryPort::class);
        $repository->shouldReceive('listPaginated')->once()->andReturn(new PaginatedResult(
            items: [
                [
                    'id' => 1,
                    'generated_at' => new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
                    'total_count' => 2,
                    'summary' => [['type_code' => 'A', 'type_name' => 'A', 'quantity' => 2, 'size' => null, 'description' => null]],
                    'generated_by' => ['id' => 3, 'email' => 'a@example.com'],
                ],
                [
                    'id' => 2,
                    'generated_at' => new DateTimeImmutable('2026-04-02T13:30:00+00:00'),
                    'total_count' => 1,
                    'summary' => [],
                    'generated_by' => null,
                ],
            ],
            currentPage: 3,
            perPage: 2,
            total: 5,
            lastPage: 3,
        ));

        $handler = new ListGenerationHistoryHandler($repository);
        $result = $handler->handle(new ListGenerationHistoryQuery(page: 3, perPage: 2));

        expect($result->items)->toHaveCount(2)
            ->and($result->items[0])->toHaveKeys(['id', 'generated_at', 'total_count', 'summary', 'generated_by'])
            ->and($result->currentPage)->toBe(3)
            ->and($result->perPage)->toBe(2)
            ->and($result->total)->toBe(5)
            ->and($result->lastPage)->toBe(3);
    });

    it('includes user info when generatedBy is present', function () {
        /** @var MockInterface&GenerationHistoryRepositoryPort $repository */
        $repository = Mockery::mock(GenerationHistoryRepositoryPort::class);
        $repository->shouldReceive('listPaginated')->once()->andReturn(new PaginatedResult(
            items: [[
                'id' => 1,
                'generated_at' => new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
                'total_count' => 1,
                'summary' => [],
                'generated_by' => ['id' => 3, 'email' => 'user@example.com'],
            ]],
            currentPage: 1,
            perPage: 15,
            total: 1,
            lastPage: 1,
        ));

        $handler = new ListGenerationHistoryHandler($repository);
        $result = $handler->handle(new ListGenerationHistoryQuery());

        expect($result->items[0]['generated_by'])->toBe(['id' => 3, 'email' => 'user@example.com']);
    });

    it('returns null generatedBy when generatedBy data is null', function () {
        /** @var MockInterface&GenerationHistoryRepositoryPort $repository */
        $repository = Mockery::mock(GenerationHistoryRepositoryPort::class);
        $repository->shouldReceive('listPaginated')->once()->andReturn(new PaginatedResult(
            items: [[
                'id' => 1,
                'generated_at' => new DateTimeImmutable('2026-04-02T12:30:00+00:00'),
                'total_count' => 1,
                'summary' => [],
                'generated_by' => null,
            ]],
            currentPage: 1,
            perPage: 15,
            total: 1,
            lastPage: 1,
        ));

        $handler = new ListGenerationHistoryHandler($repository);
        $result = $handler->handle(new ListGenerationHistoryQuery());

        expect($result->items[0]['generated_by'])->toBeNull();
    });
});
