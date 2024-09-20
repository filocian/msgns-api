<?php

declare(strict_types=1);

use Carbon\Carbon;

if (!function_exists('parseLocalizedDateTimeString')) {
	/**
	 * Converts a localized (given timezone, default is UTC) date time string (Y-m-d H:i:s) into a Carbon instance
	 *
	 * @param string $timezone
	 * @param string $dateTime
	 * @return Carbon|null
	 */
	function parseLocalizedDateTimeString(string $dateTime, string $timezone = 'UTC'): ?Carbon
	{
		return Carbon::createFromFormat('Y-m-d H:i:s', $dateTime, $timezone);
	}
}

if (!function_exists('normalizeCarbonInstance')) {
	/**
	 * Sets the timezone of a given Carbon instance to UTC. Useful for date comparisons in database or system date.
	 *
	 * @param Carbon $date
	 * @return Carbon|null
	 */
	function normalizeCarbonInstance(Carbon $date): ?Carbon
	{
		$timezone = 'UTC';

		return $date->setTimezone($timezone);
	}
}

if (!function_exists('denormalizeCarbonInstance')) {
	/**
	 * Sets the timezone of a given Carbon instance to given timezone.
	 *
	 * @param string $datetime
	 * @param string $timezone
	 * @return Carbon|null
	 */
	function denormalizeCarbonInstance(string $datetime, string $timezone): ?Carbon
	{
		$carbonDatetime = parseLocalizedDateTimeString($datetime);

		return $carbonDatetime->setTimezone($timezone);
	}
}
