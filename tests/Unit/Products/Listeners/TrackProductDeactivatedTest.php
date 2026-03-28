<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductDeactivated;
use Src\Products\Infrastructure\Listeners\TrackProductDeactivated;
use Src\Shared\Core\Ports\AnalyticsPort;

it('tracks PRODUCT_DISABLED with expected payload', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')
        ->once()
        ->with('PRODUCT_DISABLED', ['product_id' => 102, 'active' => false]);

    $listener = new TrackProductDeactivated($analytics);
    $listener->handle(new ProductDeactivated(productId: 102));
});

it('swallows analytics errors and logs them', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')->once()->andThrow(new RuntimeException('Mixpanel down'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(static fn (string $message, array $context): bool =>
            $message === 'Analytics tracking failed for PRODUCT_DISABLED'
            && $context['product_id'] === 102
            && str_contains($context['error'], 'Mixpanel down'));

    $listener = new TrackProductDeactivated($analytics);

    expect(fn () => $listener->handle(new ProductDeactivated(productId: 102)))->not->toThrow(Throwable::class);
});

afterEach(fn () => Mockery::close());
