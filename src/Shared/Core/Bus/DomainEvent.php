<?php

declare(strict_types=1);

namespace Src\Shared\Core\Bus;

interface DomainEvent
{
	public function eventName(): string;
}
