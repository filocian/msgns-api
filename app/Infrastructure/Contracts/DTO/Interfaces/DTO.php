<?php

declare(strict_types=1);

namespace App\Infrastructure\Contracts\DTO\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface DTO
{
	public static function fromModel(Model $model);
	public function toArray(string $wrapper = null, array $exclude = []): array;
	public function wrapped(string $wrapper = null): array;
}
