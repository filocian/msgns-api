<?php

declare(strict_types=1);

use Carbon\Carbon;
use Src\Shared\Core\Helpers\DateTimeConverter;

describe('DateTimeConverter', function () {
	it('converts utc timestamp to target timezone', function () {
		$dateTime = DateTimeConverter::fromUtcTimestamp(1718451000, 'Europe/Madrid');

		expect($dateTime->format('Y-m-d H:i:s P'))->toBe('2024-06-15 13:30:00 +02:00');
	});

	it('converts datetime from any timezone to utc', function () {
		$dateTime = new DateTimeImmutable('2025-06-15T14:30:00+02:00');

		expect(DateTimeConverter::toUtc($dateTime)->format(DateTimeInterface::ATOM))->toBe('2025-06-15T12:30:00+00:00');
	});

	it('accepts carbon instances when converting to utc', function () {
		$dateTime = Carbon::parse('2025-06-15 14:30:00', 'Europe/Madrid');

		$converted = DateTimeConverter::toUtc($dateTime);

		expect($converted)->toBeInstanceOf(Carbon::class)
			->and($converted->format(DateTimeInterface::ATOM))->toBe('2025-06-15T12:30:00+00:00')
			->and($dateTime->format(DateTimeInterface::ATOM))->toBe('2025-06-15T14:30:00+02:00');
	});

	it('parses iso 8601 string with timezone info to utc', function () {
		expect(DateTimeConverter::fromString('2025-06-15T14:30:00+02:00')->format(DateTimeInterface::ATOM))
			->toBe('2025-06-15T12:30:00+00:00');
	});

	it('parses iso 8601 string without timezone info using source timezone', function () {
		expect(DateTimeConverter::fromString('2025-06-15T14:30:00', 'Europe/Madrid')->format(DateTimeInterface::ATOM))
			->toBe('2025-06-15T12:30:00+00:00');
	});

	it('preserves value on round-trip conversion', function () {
		$original = new DateTimeImmutable('2025-10-31T09:15:00+00:00');
		$converted = DateTimeConverter::convert($original, 'Europe/Madrid');

		expect(DateTimeConverter::toUtc($converted)->format(DateTimeInterface::ATOM))
			->toBe($original->format(DateTimeInterface::ATOM));
	});

	it('accepts carbon instances when converting to another timezone', function () {
		$original = Carbon::parse('2025-10-31 09:15:00', 'UTC');

		$converted = DateTimeConverter::convert($original, 'Europe/Madrid');

		expect($converted)->toBeInstanceOf(Carbon::class)
			->and($converted->format(DateTimeInterface::ATOM))->toBe('2025-10-31T10:15:00+01:00')
			->and($original->format(DateTimeInterface::ATOM))->toBe('2025-10-31T09:15:00+00:00');
	});

	it('handles unix timestamp zero', function () {
		expect(DateTimeConverter::fromUtcTimestamp(0, 'Europe/Madrid')->getTimestamp())->toBe(0);
	});

	it('handles negative unix timestamp', function () {
		expect(DateTimeConverter::fromUtcTimestamp(-1, 'UTC')->getTimestamp())->toBe(-1);
	});

	it('throws on invalid timezone string', function () {
		DateTimeConverter::fromUtcTimestamp(1718451000, 'Mars/Olympus');
	})->throws(InvalidArgumentException::class, 'Invalid timezone');

	it('handles dst boundary correctly', function () {
		$dateTime = DateTimeConverter::fromString('2025-03-30T01:30:00', 'UTC');

		expect(DateTimeConverter::convert($dateTime, 'Europe/Madrid')->format('P'))->toBe('+02:00');
	});

	it('preserves milliseconds', function () {
		$dateTime = DateTimeConverter::fromUtcTimestamp(1718451000.123, 'UTC');

		expect($dateTime->format('u'))->toBe('123000');
	});

	it('accepts carbon instances when converting to a timestamp', function () {
		$dateTime = Carbon::parse('2025-06-15 14:30:00', 'Europe/Madrid');

		expect(DateTimeConverter::toTimestamp($dateTime))->toBe(1749990600);
	});

	it('treats z suffix as utc', function () {
		expect(DateTimeConverter::fromString('2025-06-15T14:30:00Z')->format(DateTimeInterface::ATOM))
			->toBe('2025-06-15T14:30:00+00:00');
	});
});
