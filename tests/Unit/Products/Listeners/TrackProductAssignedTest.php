<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductAssigned;
use Src\Products\Infrastructure\Listeners\TrackProductAssigned;
use Src\Shared\Core\Ports\AnalyticsPort;

it('tracks PRODUCT_ASSIGNATION with expected payload', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')
        ->once()
        ->with('PRODUCT_ASSIGNATION', ['user_id' => 12, 'product_id' => 107]);

    $listener = new TrackProductAssigned($analytics);
    $listener->handle(new ProductAssigned(productId: 107, userId: 12));
});

it('swallows analytics errors and logs them', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')->once()->andThrow(new RuntimeException('Mixpanel down'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(static fn (string $message, array $context): bool =>
            $message === 'Analytics tracking failed for PRODUCT_ASSIGNATION'
            && $context['user_id'] === 12
            && $context['product_id'] === 107
            && str_contains($context['error'], 'Mixpanel down'));

    $listener = new TrackProductAssigned($analytics);

    expect(fn () => $listener->handle(new ProductAssigned(productId: 107, userId: 12)))->not->toThrow(Throwable::class);
});

afterEach(fn () => Mockery::close());
