<?php

declare(strict_types=1);

namespace Src\Shared\Core\Bus;

interface Query
{
	public function queryName(): string;
}
