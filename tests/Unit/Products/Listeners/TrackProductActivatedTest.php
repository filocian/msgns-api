<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductActivated;
use Src\Products\Infrastructure\Listeners\TrackProductActivated;
use Src\Shared\Core\Ports\AnalyticsPort;

it('tracks PRODUCT_ENABLED with expected payload', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')
        ->once()
        ->with('PRODUCT_ENABLED', ['product_id' => 101, 'active' => true]);

    $listener = new TrackProductActivated($analytics);
    $listener->handle(new ProductActivated(productId: 101));
});

it('swallows analytics errors and logs them', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')->once()->andThrow(new RuntimeException('Mixpanel down'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(static fn (string $message, array $context): bool =>
            $message === 'Analytics tracking failed for PRODUCT_ENABLED'
            && $context['product_id'] === 101
            && str_contains($context['error'], 'Mixpanel down'));

    $listener = new TrackProductActivated($analytics);

    expect(fn () => $listener->handle(new ProductActivated(productId: 101)))->not->toThrow(Throwable::class);
});

afterEach(fn () => Mockery::close());
