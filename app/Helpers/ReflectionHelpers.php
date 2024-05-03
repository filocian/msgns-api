<?php

declare(strict_types=1);

use App\Infrastructure\Contracts\DTO\Interfaces\DTO;
use Carbon\Carbon;

if (!function_exists('DtoToArray')) {
	/**
	 * Transforms a DTO into array.
	 *
	 * @param DTO $object The DTO to convert into array
	 * @param array<string> $exclude if provided, will exclude all keys matched and its value from the result array
	 * @return array
	 */
	function DtoToArray(DTO $object, array $exclude = []): array
	{
		$data = [];
		$reflection = new ReflectionClass($object);
		$properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach ($properties as $property) {
			$propertyName = $property->getName();
			$value = $property->getValue($object);

			if (in_array($propertyName, $exclude)) {
				continue;
			}

			if ($value instanceof DTO) {
				$data[$propertyName] = DtoToArray($value, $exclude);
				continue;
			}

			if ($value instanceof Carbon) {
				$data[$propertyName] = $value->toDateTimeString();
				continue;
			}

			$data[$propertyName] = $value;
		}
		return $data;
	}
}
