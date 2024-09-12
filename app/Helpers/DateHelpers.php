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
