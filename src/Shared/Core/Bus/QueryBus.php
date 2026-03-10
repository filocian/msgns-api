<?php

declare(strict_types=1);

namespace Src\Shared\Core\Bus;

interface QueryBus
{
	/**
	 * @param QueryHandler|class-string<QueryHandler> $handler
	 */
	public function register(string $queryName, QueryHandler|string $handler): void;

	public function dispatch(Query $query): mixed;
}
