<?php

declare(strict_types=1);

namespace Src\Shared\Core\Bus;

interface CommandBus
{
	/**
	 * @param CommandHandler|class-string<CommandHandler> $handler
	 */
	public function register(string $commandName, CommandHandler|string $handler): void;

	public function dispatch(Command $command): mixed;
}
