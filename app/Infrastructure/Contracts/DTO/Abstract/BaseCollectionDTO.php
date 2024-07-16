<?php

declare(strict_types=1);

namespace App\Infrastructure\Contracts\DTO\Abstract;

use App\Infrastructure\Contracts\DTO\Interfaces\CollectionDTO;
use App\Infrastructure\Contracts\DTO\Interfaces\DTO;
use Illuminate\Support\Collection as SupportCollection;
use InvalidArgumentException;

abstract class BaseCollectionDTO implements CollectionDTO
{
	/**
	 * Creates a DTO Collection from a given Collection
	 *
	 * @param SupportCollection $collection
	 * @param string $dtoClass
	 * @return BaseCollectionDTO
	 */
	public static function fromModelCollection(SupportCollection $collection, string $dtoClass): static
	{
		if (!in_array(DTO::class, class_implements($dtoClass))) {
			throw new InvalidArgumentException("Class '{$dtoClass}' must implement 'DTO' interface");
		}

		return new static($collection->map(function ($paginatedModel) use ($dtoClass) {
			return $dtoClass::fromModel($paginatedModel);
		}));
	}

	/**
	 * Creates an array from current CollectionDTO instance
	 *
	 * @param string|null $wrapper if provided, wraps the result under the provided wrapper name
	 * @param array $exclude if provided, excludes the specified properties from the result array
	 * @return array
	 */
	public function toArray(string $wrapper = null, array $exclude = []): array
	{
		$collectionData = $this->data->map(function ($item) use ($exclude) {
			return $item->toArray(null, $exclude);
		})->toArray();

		if ($wrapper) {
			return [
				$wrapper => $collectionData,
			];
		}

		return $collectionData;
	}

	public function wrapped(string $wrapper = null): array
	{
		$data = $this->data->map(function ($item) {
			return $item;
		});

		if ($wrapper) {
			return [
				$wrapper => $data,
			];
		}

		return [$data];
	}
}
