<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductBusinessUpdated;
use Src\Products\Infrastructure\Listeners\TrackProductBusinessUpdated;
use Src\Shared\Core\Ports\AnalyticsPort;

it('tracks PRODUCT_BUSINESS with expected payload', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')
        ->once()
        ->with('PRODUCT_BUSINESS', ['product_id' => 106, 'business_data' => ['company' => 'Acme']]);

    $listener = new TrackProductBusinessUpdated($analytics);
    $listener->handle(new ProductBusinessUpdated(productId: 106, businessData: ['company' => 'Acme']));
});

it('swallows analytics errors and logs them', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')->once()->andThrow(new RuntimeException('Mixpanel down'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(static fn (string $message, array $context): bool =>
            $message === 'Analytics tracking failed for PRODUCT_BUSINESS'
            && $context['product_id'] === 106
            && str_contains($context['error'], 'Mixpanel down'));

    $listener = new TrackProductBusinessUpdated($analytics);

    expect(fn () => $listener->handle(new ProductBusinessUpdated(productId: 106, businessData: ['company' => 'Acme'])))->not->toThrow(Throwable::class);
});

afterEach(fn () => Mockery::close());
