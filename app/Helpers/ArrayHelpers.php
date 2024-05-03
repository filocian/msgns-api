<?php

declare(strict_types=1);

if (!function_exists('unsetArrayEntriesByKey')) {
	/**
	 * Removes entries from a given array based on the provided keys array
	 *
	 * @param array $array
	 * @param array $keys
	 * @return void
	 */
	function unsetArrayEntriesByKey(array &$array, array $keys): void
	{
		dump($array);
		foreach ($array as $key => &$value) {
			dump('entra foreach');
			if (in_array($key, $keys)) {
				dump('entra if1');
				unset($array[$key]);
			} elseif (is_array($value)) {
				dump('entra if2');
				unsetArrayEntriesByKey($value, $keys);
			}
		}
		unset($value);
	}
}
