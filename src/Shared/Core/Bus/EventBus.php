<?php

declare(strict_types=1);

namespace Src\Shared\Core\Bus;

interface EventBus
{
	public function publish(DomainEvent $event): void;
}
