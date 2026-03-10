<?php

declare(strict_types=1);

namespace Src\Shared\Core\Helpers;

use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Throwable;

/**
 * Shared date conversion helpers that preserve immutable behavior.
 */
final class DateTimeConverter
{
	/**
	 * Creates a localized immutable date from a UTC timestamp.
	 */
	public static function fromUtcTimestamp(int|float $timestamp, string $timezone): DateTimeImmutable
	{
		return self::createUtcDateTime($timestamp)->setTimezone(self::timezone($timezone));
	}

	/**
	 * Converts the given date-like object to UTC while preserving its input type.
	 *
	 * @return DateTimeImmutable|Carbon
	 */
	public static function toUtc(DateTimeImmutable|Carbon $dateTime): DateTimeImmutable|Carbon
	{
		return self::restoreType(
			$dateTime,
			self::toImmutable($dateTime)->setTimezone(self::timezone('UTC')),
		);
	}

	/**
	 * Parses a date string and normalizes it to UTC.
	 */
	public static function fromString(string $dateString, ?string $sourceTimezone = null): DateTimeImmutable
	{
		try {
			$hasTimezone = (bool) preg_match('/(Z|[+\-]\d{2}:\d{2})$/', $dateString);

			if ($hasTimezone) {
				return self::toImmutable(self::toUtc(new DateTimeImmutable($dateString)));
			}

			if ($sourceTimezone === null) {
				throw new InvalidArgumentException('A source timezone is required when the date string has no timezone information.');
			}

			return self::toImmutable(self::toUtc(new DateTimeImmutable($dateString, self::timezone($sourceTimezone))));
		} catch (InvalidArgumentException $exception) {
			throw $exception;
		} catch (Throwable $exception) {
			throw new InvalidArgumentException($exception->getMessage(), 0, $exception);
		}
	}

	/**
	 * Normalizes a date-like object to a UTC unix timestamp.
	 */
	public static function toTimestamp(DateTimeImmutable|Carbon $dateTime): int
	{
		return self::toUtc($dateTime)->getTimestamp();
	}

	/**
	 * Converts the given date-like object to the target timezone while preserving its input type.
	 *
	 * @return DateTimeImmutable|Carbon
	 */
	public static function convert(DateTimeImmutable|Carbon $dateTime, string $targetTimezone): DateTimeImmutable|Carbon
	{
		return self::restoreType(
			$dateTime,
			self::toImmutable($dateTime)->setTimezone(self::timezone($targetTimezone)),
		);
	}

	/**
	 * Creates a UTC immutable date from a timestamp.
	 */
	private static function createUtcDateTime(int|float $timestamp): DateTimeImmutable
	{
		$normalizedTimestamp = sprintf('%.6F', $timestamp);
		$dateTime = DateTimeImmutable::createFromFormat('U.u', $normalizedTimestamp, self::timezone('UTC'));

		if ($dateTime === false) {
			throw new InvalidArgumentException(sprintf('Unable to parse timestamp [%s].', $normalizedTimestamp));
		}

		return $dateTime;
	}

	/**
	 * Converts any supported date-like input to an immutable instance.
	 */
	private static function toImmutable(DateTimeImmutable|Carbon $dateTime): DateTimeImmutable
	{
		if ($dateTime instanceof Carbon) {
			return $dateTime->toImmutable();
		}

		return $dateTime;
	}

	/**
	 * Restores the original input type after conversion.
	 *
	 * @return DateTimeImmutable|Carbon
	 */
	private static function restoreType(DateTimeImmutable|Carbon $original, DateTimeImmutable $converted): DateTimeImmutable|Carbon
	{
		if ($original instanceof Carbon) {
			return Carbon::instance($converted);
		}

		return $converted;
	}

	/**
	 * Creates a validated timezone instance.
	 */
	private static function timezone(string $timezone): DateTimeZone
	{
		try {
			return new DateTimeZone($timezone);
		} catch (Throwable $exception) {
			throw new InvalidArgumentException(sprintf('Invalid timezone [%s].', $timezone), 0, $exception);
		}
	}
}
