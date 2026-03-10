<?php

declare(strict_types=1);

use Src\Shared\Core\Helpers\PasswordGenerator;

describe('PasswordGenerator', function () {
	it('generates password with correct length and all char types', function () {
		$password = PasswordGenerator::generate(16, true);

		expect(strlen($password))->toBe(16)
			->and($password)->toMatch('/[A-Z]/')
			->and($password)->toMatch('/[a-z]/')
			->and($password)->toMatch('/[0-9]/')
			->and($password)->toMatch('/[^A-Za-z0-9]/');
	});

	it('generates alphanumeric-only password when special chars disabled', function () {
		$password = PasswordGenerator::generate(12, false);

		expect(strlen($password))->toBe(12)
			->and($password)->toMatch('/^[A-Za-z0-9]+$/');
	});

	it('uses sensible defaults', function () {
		$password = PasswordGenerator::generate();

		expect(strlen($password))->toBe(16)
			->and($password)->toMatch('/[^A-Za-z0-9]/');
	});

	it('throws when length is less than 8', function () {
		PasswordGenerator::generate(1);
	})->throws(InvalidArgumentException::class, 'at least 8');

	it('throws when length is zero or negative', function () {
		PasswordGenerator::generate(0);
	})->throws(InvalidArgumentException::class, 'at least 8');

	it('meets all char type requirements at minimum length', function () {
		$password = PasswordGenerator::generate(8, true);

		expect(strlen($password))->toBe(8)
			->and($password)->toMatch('/[A-Z]/')
			->and($password)->toMatch('/[a-z]/')
			->and($password)->toMatch('/[0-9]/')
			->and($password)->toMatch('/[^A-Za-z0-9]/');
	});
});
