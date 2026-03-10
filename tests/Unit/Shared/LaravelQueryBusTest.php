<?php

declare(strict_types=1);

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Shared\Infrastructure\Bus\LaravelQueryBus;

final readonly class TestQuery implements Query
{
	public function __construct(public int $value) {}

	public function queryName(): string
	{
		return 'shared.test_query';
	}
}

final class TestQueryHandler implements QueryHandler
{
	public function handle(Query $query): mixed
	{
		assert($query instanceof TestQuery);

		return $query->value * 2;
	}
}

describe('LaravelQueryBus', function () {
	it('dispatches query to registered handler', function () {
		$bus = new LaravelQueryBus(app());
		$bus->register('shared.test_query', TestQueryHandler::class);

		expect($bus->dispatch(new TestQuery(21)))->toBe(42);
	});

	it('throws when no handler registered for query', function () {
		$bus = new LaravelQueryBus(app());

		$bus->dispatch(new TestQuery(21));
	})->throws(RuntimeException::class, 'shared.test_query');
});
