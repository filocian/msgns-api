<?php

declare(strict_types=1);

namespace App\Infrastructure\Contracts\DTO\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;

interface PaginatorDTO
{
	public static function fromPaginator(LengthAwarePaginator $paginator, string $dtoClass);
	public function toArray(string $wrapper = null, array $exclude = []): array;
	public function wrapped(string $wrapper = null): array;
}
