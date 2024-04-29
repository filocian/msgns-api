<?php

declare(strict_types=1);

namespace App\Infrastructure\Contracts;

interface UseCaseContract
{
	public function run(mixed $data = null, ?array $opts = null);
}
