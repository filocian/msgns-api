<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductRenamed;
use Src\Products\Infrastructure\Listeners\TrackProductRenamed;
use Src\Shared\Core\Ports\AnalyticsPort;

it('tracks PRODUCT_NAMING with expected payload', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')
        ->once()
        ->with('PRODUCT_NAMING', ['product_id' => 104, 'name' => 'Renamed Product']);

    $listener = new TrackProductRenamed($analytics);
    $listener->handle(new ProductRenamed(productId: 104, name: 'Renamed Product'));
});

it('swallows analytics errors and logs them', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')->once()->andThrow(new RuntimeException('Mixpanel down'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(static fn (string $message, array $context): bool =>
            $message === 'Analytics tracking failed for PRODUCT_NAMING'
            && $context['product_id'] === 104
            && str_contains($context['error'], 'Mixpanel down'));

    $listener = new TrackProductRenamed($analytics);

    expect(fn () => $listener->handle(new ProductRenamed(productId: 104, name: 'Renamed Product')))->not->toThrow(Throwable::class);
});

afterEach(fn () => Mockery::close());
