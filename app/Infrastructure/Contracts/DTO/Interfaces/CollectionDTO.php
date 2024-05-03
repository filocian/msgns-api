<?php

declare(strict_types=1);

namespace App\Infrastructure\Contracts\DTO\Interfaces;

use Illuminate\Support\Collection;

interface CollectionDTO
{
	public static function fromModelCollection(Collection $collection, string $dtoClass);
	public function toArray(string $wrapper = null, array $exclude = []): array;
	public function wrapped(string $wrapper = null): array;
}
