<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Bus;

use Illuminate\Contracts\Events\Dispatcher;
use Src\Shared\Core\Bus\DomainEvent;
use Src\Shared\Core\Bus\EventBus;

final class LaravelEventBus implements EventBus
{
	public function __construct(private readonly Dispatcher $dispatcher) {}

	public function publish(DomainEvent $event): void
	{
		$this->dispatcher->dispatch($event);
	}
}
