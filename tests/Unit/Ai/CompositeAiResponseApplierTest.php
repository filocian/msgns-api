<?php

declare(strict_types=1);

use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\Services\CompositeAiResponseApplier;
use Src\Ai\Infrastructure\Persistence\AiResponseRecord;
use Src\Shared\Core\Errors\NotFound;

describe('CompositeAiResponseApplier', function (): void {

    beforeEach(function (): void {
        Mockery::close();
    });

    afterEach(fn () => Mockery::close());

    function makeRecord(string $productType): AiResponseRecord
    {
        $record = new AiResponseRecord;
        $record->product_type = $productType;

        return $record;
    }

    // ─── supports() ───────────────────────────────────────────────────────────

    it('supports() returns true when at least one applier supports the product type', function (): void {
        $applier = Mockery::mock(AiResponseApplierPort::class);
        $applier->shouldReceive('supports')->with('google_review')->andReturn(true);

        $composite = new CompositeAiResponseApplier([$applier]);

        expect($composite->supports('google_review'))->toBeTrue();
    });

    it('supports() returns false when no applier supports the product type', function (): void {
        $applier = Mockery::mock(AiResponseApplierPort::class);
        $applier->shouldReceive('supports')->with('google_review')->andReturn(false);

        $composite = new CompositeAiResponseApplier([$applier]);

        expect($composite->supports('google_review'))->toBeFalse();
    });

    it('supports() returns false with empty appliers array', function (): void {
        $composite = new CompositeAiResponseApplier([]);

        expect($composite->supports('google_review'))->toBeFalse();
    });

    // ─── apply() ──────────────────────────────────────────────────────────────

    it('delegates apply() to the first matching applier', function (): void {
        $record = makeRecord('google_review');

        $matchingApplier = Mockery::mock(AiResponseApplierPort::class);
        $matchingApplier->shouldReceive('supports')->with('google_review')->andReturn(true);
        $matchingApplier->shouldReceive('apply')->once()->with($record);

        $composite = new CompositeAiResponseApplier([$matchingApplier]);
        $composite->apply($record);
    });

    it('stops at the first matching applier and does not call others', function (): void {
        $record = makeRecord('google_review');

        $first = Mockery::mock(AiResponseApplierPort::class);
        $first->shouldReceive('supports')->with('google_review')->andReturn(true);
        $first->shouldReceive('apply')->once()->with($record);

        $second = Mockery::mock(AiResponseApplierPort::class);
        $second->shouldNotReceive('supports');
        $second->shouldNotReceive('apply');

        $composite = new CompositeAiResponseApplier([$first, $second]);
        $composite->apply($record);
    });

    it('apply() throws NotFound when no applier matches', function (): void {
        $record = makeRecord('unknown_type');

        $applier = Mockery::mock(AiResponseApplierPort::class);
        $applier->shouldReceive('supports')->with('unknown_type')->andReturn(false);

        $composite = new CompositeAiResponseApplier([$applier]);

        expect(fn () => $composite->apply($record))->toThrow(NotFound::class);
    });

    it('apply() throws NotFound with empty appliers array', function (): void {
        $record = makeRecord('google_review');
        $composite = new CompositeAiResponseApplier([]);

        expect(fn () => $composite->apply($record))->toThrow(NotFound::class);
    });
});
