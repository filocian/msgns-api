<?php

declare(strict_types=1);

namespace Src\Shared\Core\Bus;

interface CommandHandler
{
	public function handle(Command $command): mixed;
}
