<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductTargetUrlSet;
use Src\Products\Infrastructure\Listeners\TrackProductTargetUrlSet;
use Src\Shared\Core\Ports\AnalyticsPort;

it('tracks PRODUCT_CONFIGURATION with expected payload', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')
        ->once()
        ->with('PRODUCT_CONFIGURATION', ['product_id' => 103, 'target_url' => 'https://example.com']);

    $listener = new TrackProductTargetUrlSet($analytics);
    $listener->handle(new ProductTargetUrlSet(productId: 103, targetUrl: 'https://example.com'));
});

it('swallows analytics errors and logs them', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')->once()->andThrow(new RuntimeException('Mixpanel down'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(static fn (string $message, array $context): bool =>
            $message === 'Analytics tracking failed for PRODUCT_CONFIGURATION'
            && $context['product_id'] === 103
            && str_contains($context['error'], 'Mixpanel down'));

    $listener = new TrackProductTargetUrlSet($analytics);

    expect(fn () => $listener->handle(new ProductTargetUrlSet(productId: 103, targetUrl: 'https://example.com')))->not->toThrow(Throwable::class);
});

afterEach(fn () => Mockery::close());
