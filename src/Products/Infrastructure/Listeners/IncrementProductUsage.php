<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\Events\ProductScanned;

final class IncrementProductUsage implements ShouldQueue
{
    public function handle(ProductScanned $event): void
    {
        DB::table('products')
            ->where('id', $event->productId)
            ->increment('usage');
    }
}
