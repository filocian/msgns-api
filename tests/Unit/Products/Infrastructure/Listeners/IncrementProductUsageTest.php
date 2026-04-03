<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Src\Products\Domain\Events\ProductScanned;
use Src\Products\Infrastructure\Listeners\IncrementProductUsage;

describe('IncrementProductUsage', function () {
    it('implements queued execution and increments the product usage column', function () {
        /** @var MockInterface $query */
        $query = Mockery::mock();
        DB::shouldReceive('table')->once()->with('products')->andReturn($query);
        $query->shouldReceive('where')->once()->with('id', 42)->andReturnSelf();
        $query->shouldReceive('increment')->once()->with('usage')->andReturn(1);

        $listener = new IncrementProductUsage();

        expect($listener)->toBeInstanceOf(ShouldQueue::class);

        $listener->handle(new ProductScanned(42, 7, 'Product', new DateTimeImmutable('2024-01-01T00:00:00+00:00')));
    });

    it('does not crash when the product row no longer exists', function () {
        /** @var MockInterface $query */
        $query = Mockery::mock();
        DB::shouldReceive('table')->once()->with('products')->andReturn($query);
        $query->shouldReceive('where')->once()->with('id', 999)->andReturnSelf();
        $query->shouldReceive('increment')->once()->with('usage')->andReturn(0);

        $listener = new IncrementProductUsage();

        $listener->handle(new ProductScanned(999, null, 'Missing Product', new DateTimeImmutable('2024-01-01T00:00:00+00:00')));
    });
});
