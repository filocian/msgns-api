<?php

declare(strict_types=1);

use Src\Shared\Core\Bus\DomainEvent;
use Src\Shared\Infrastructure\Bus\LaravelEventBus;

final readonly class TestDomainEvent implements DomainEvent
{
	public function __construct(public string $payload) {}

	public function eventName(): string
	{
		return 'shared.test_event';
	}
}

describe('LaravelEventBus', function () {
	it('publishes event to laravel dispatcher', function () {
		$receivedPayload = null;
		app('events')->listen(TestDomainEvent::class, function (TestDomainEvent $event) use (&$receivedPayload) {
			$receivedPayload = $event->payload;
		});

		$bus = new LaravelEventBus(app('events'));
		$bus->publish(new TestDomainEvent('received'));

		expect($receivedPayload)->toBe('received');
	});
});
