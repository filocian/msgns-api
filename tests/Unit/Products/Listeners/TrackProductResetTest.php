<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductReset;
use Src\Products\Infrastructure\Listeners\TrackProductReset;
use Src\Shared\Core\Ports\AnalyticsPort;

it('tracks PRODUCT_RESET with expected payload', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')
        ->once()
        ->with('PRODUCT_RESET', ['product_id' => 105]);

    $listener = new TrackProductReset($analytics);
    $listener->handle(new ProductReset(productId: 105));
});

it('swallows analytics errors and logs them', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')->once()->andThrow(new RuntimeException('Mixpanel down'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(static fn (string $message, array $context): bool =>
            $message === 'Analytics tracking failed for PRODUCT_RESET'
            && $context['product_id'] === 105
            && str_contains($context['error'], 'Mixpanel down'));

    $listener = new TrackProductReset($analytics);

    expect(fn () => $listener->handle(new ProductReset(productId: 105)))->not->toThrow(Throwable::class);
});

afterEach(fn () => Mockery::close());
