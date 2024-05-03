<?php

declare(strict_types=1);

namespace App\Infrastructure\Contracts\DTO\Abstract;

use App\Infrastructure\Contracts\DTO\Interfaces\DTO;
use Illuminate\Database\Eloquent\Model;

abstract class BaseDTO implements DTO
{
	/**
	 * Creates a DTO from a given Eloquent Model
	 *
	 * @param Model $model
	 * @return static
	 */
	public static function fromModel(Model $model): static
	{
		return new static($model);
	}

	/**
	 * Creates an array from current DTO instance
	 *
	 * @param string|null $wrapper if provided, wraps the result under the provided wrapper name
	 * @param array $exclude if provided, excludes the specified properties from the result array
	 * @return array|array[]
	 */
	public function toArray(string $wrapper = null, array $exclude = []): array
	{
		$data = DtoToArray($this, $exclude);

		if ($wrapper) {
			return [
				$wrapper => $data,
			];
		}

		return $data;
	}

	public function wrapped(string $wrapper = null): array
	{
		if ($wrapper) {
			return [
				$wrapper => $this,
			];
		}

		return [$this];
	}
}
