<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductsPaired;
use Src\Products\Infrastructure\Listeners\TrackProductsPaired;
use Src\Shared\Core\Ports\AnalyticsPort;

it('tracks PRODUCT_PAIRING with remapped payload keys', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')
        ->once()
        ->with('PRODUCT_PAIRING', ['main_product' => 201, 'child_product' => 202]);

    $listener = new TrackProductsPaired($analytics);
    $listener->handle(new ProductsPaired(mainProductId: 201, childProductId: 202));
});

it('swallows analytics errors and logs them', function () {
    $analytics = Mockery::mock(AnalyticsPort::class);
    $analytics->shouldReceive('track')->once()->andThrow(new RuntimeException('Mixpanel down'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(static fn (string $message, array $context): bool =>
            $message === 'Analytics tracking failed for PRODUCT_PAIRING'
            && $context['main_product'] === 201
            && $context['child_product'] === 202
            && str_contains($context['error'], 'Mixpanel down'));

    $listener = new TrackProductsPaired($analytics);

    expect(fn () => $listener->handle(new ProductsPaired(mainProductId: 201, childProductId: 202)))->not->toThrow(Throwable::class);
});

afterEach(fn () => Mockery::close());
