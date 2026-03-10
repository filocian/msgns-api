<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Bus;

use Illuminate\Contracts\Container\Container;
use RuntimeException;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\CommandHandler;

final class LaravelCommandBus implements CommandBus
{
	/**
	 * @var array<string, CommandHandler|class-string<CommandHandler>>
	 */
	private array $handlers = [];

	public function __construct(private readonly Container $container) {}

	public function register(string $commandName, CommandHandler|string $handler): void
	{
		$this->handlers[$commandName] = $handler;
	}

	public function dispatch(Command $command): mixed
	{
		$handler = $this->handlers[$command->commandName()] ?? null;

		if ($handler === null) {
			throw new RuntimeException(sprintf('No command handler registered for [%s].', $command->commandName()));
		}

		return $this->resolveHandler($handler)->handle($command);
	}

	/**
	 * @param CommandHandler|class-string<CommandHandler> $handler
	 */
	private function resolveHandler(CommandHandler|string $handler): CommandHandler
	{
		if ($handler instanceof CommandHandler) {
			return $handler;
		}

		return $this->container->make($handler);
	}
}
