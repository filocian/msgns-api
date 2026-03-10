<?php

declare(strict_types=1);

use Src\Shared\Core\Helpers\UuidGenerator;

describe('UuidGenerator', function () {
	it('generates a valid uuid v4', function () {
		expect(UuidGenerator::generate())
			->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
	});

	it('generates unique values on each call', function () {
		expect(UuidGenerator::generate())->not->toBe(UuidGenerator::generate());
	});

	it('matches uuid v4 format strictly', function () {
		expect(UuidGenerator::generate())
			->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
	});
});
