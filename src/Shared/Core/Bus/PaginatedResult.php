<?php

declare(strict_types=1);

namespace Src\Shared\Core\Bus;

final readonly class PaginatedResult
{
	/**
	 * @param array<int, mixed> $items
	 * @param array<string, mixed>|null $overview
	 */
	public function __construct(
		public array $items,
		public int $currentPage,
		public int $perPage,
		public int $total,
		public int $lastPage,
		public ?array $overview = null,
	) {}
}
