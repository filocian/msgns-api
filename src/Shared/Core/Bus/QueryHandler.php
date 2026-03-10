<?php

declare(strict_types=1);

namespace Src\Shared\Core\Bus;

interface QueryHandler
{
	public function handle(Query $query): mixed;
}
