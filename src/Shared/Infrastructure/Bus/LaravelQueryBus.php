<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Bus;

use Illuminate\Contracts\Container\Container;
use RuntimeException;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Core\Bus\QueryHandler;

final class LaravelQueryBus implements QueryBus
{
	/**
	 * @var array<string, QueryHandler|class-string<QueryHandler>>
	 */
	private array $handlers = [];

	public function __construct(private readonly Container $container) {}

	public function register(string $queryName, QueryHandler|string $handler): void
	{
		$this->handlers[$queryName] = $handler;
	}

	public function dispatch(Query $query): mixed
	{
		$handler = $this->handlers[$query->queryName()] ?? null;

		if ($handler === null) {
			throw new RuntimeException(sprintf('No query handler registered for [%s].', $query->queryName()));
		}

		return $this->resolveHandler($handler)->handle($query);
	}

	/**
	 * @param QueryHandler|class-string<QueryHandler> $handler
	 */
	private function resolveHandler(QueryHandler|string $handler): QueryHandler
	{
		if ($handler instanceof QueryHandler) {
			return $handler;
		}

		return $this->container->make($handler);
	}
}
