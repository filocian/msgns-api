<?php

declare(strict_types=1);

namespace App\Infrastructure\Contracts\DTO\Abstract;

use App\Infrastructure\Contracts\DTO\Interfaces\DTO;
use App\Infrastructure\Contracts\DTO\Interfaces\PaginatorDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

abstract class BasePaginatorDTO implements PaginatorDTO
{
	public static function fromPaginator(LengthAwarePaginator $paginator, string $dtoClass): static
	{
		if (!in_array(DTO::class, class_implements($dtoClass))) {
			throw new InvalidArgumentException("Class '{$dtoClass}' must implement 'DTO' interface");
		}

		$collection = new Collection([
			'items' => $paginator->getCollection()->map(function ($paginatedModel) use ($dtoClass) {
				return $dtoClass::fromModel($paginatedModel);
			}),
			'pagination' => [
				'currentPage' => $paginator->currentPage(),
				'lastPage' => $paginator->lastPage(),
				'perPage' => $paginator->perPage(),
				'total' => $paginator->total(),
				'pageCount' => $paginator->count(),
				'hasMorePages' => $paginator->hasMorePages(),
			],
		]);

		return new static($collection);
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
		$data = $this->data->get('items')->map(function ($item) use ($exclude) {
			return $item->toArray(null, $exclude);
		});

		if ($wrapper) {
			return [
				$wrapper => $data->toArray(),
				'pagination' => $this->data->get('pagination'),
			];
		}

		return [
			'items' => $data->toArray(),
			'pagination' => $this->data->get('pagination'),
		];
	}

	public function wrapped(string $wrapper = null): array
	{
		$data = $this->data->get('items')->map(function ($item) {
			return $item;
		});

		if ($wrapper) {
			return [
				$wrapper => $data->toArray(),
				'pagination' => $this->data->get('pagination'),
			];
		}

		return [
			'items' => $data->toArray(),
			'pagination' => $this->data->get('pagination'),
		];
	}
}
