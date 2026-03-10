<?php

declare(strict_types=1);

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Infrastructure\Bus\LaravelCommandBus;

final readonly class TestCommand implements Command
{
	public function __construct(public string $value) {}

	public function commandName(): string
	{
		return 'shared.test_command';
	}
}

final class TestCommandHandler implements CommandHandler
{
	public function handle(Command $command): mixed
	{
		assert($command instanceof TestCommand);

		return strtoupper($command->value);
	}
}

describe('LaravelCommandBus', function () {
	it('dispatches command to registered handler', function () {
		$bus = new LaravelCommandBus(app());
		$bus->register('shared.test_command', TestCommandHandler::class);

		expect($bus->dispatch(new TestCommand('hello')))->toBe('HELLO');
	});

	it('throws when no handler registered for command', function () {
		$bus = new LaravelCommandBus(app());

		$bus->dispatch(new TestCommand('hello'));
	})->throws(RuntimeException::class, 'shared.test_command');

	it('overwrites handler on duplicate registration', function () {
		$bus = new LaravelCommandBus(app());
		$bus->register('shared.test_command', new class implements CommandHandler {
			public function handle(Command $command): mixed
			{
				return 'FIRST';
			}
		});
		$bus->register('shared.test_command', new class implements CommandHandler {
			public function handle(Command $command): mixed
			{
				return 'SECOND';
			}
		});

		expect($bus->dispatch(new TestCommand('hello')))->toBe('SECOND');
	});

	it('lets domain exception propagate', function () {
		$bus = new LaravelCommandBus(app());
		$bus->register('shared.test_command', new class implements CommandHandler {
			public function handle(Command $command): mixed
			{
				throw ValidationFailed::because('boom');
			}
		});

		$bus->dispatch(new TestCommand('hello'));
	})->throws(ValidationFailed::class, 'boom');
});
