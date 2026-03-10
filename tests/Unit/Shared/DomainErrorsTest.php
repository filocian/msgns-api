<?php

declare(strict_types=1);

use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\Unauthorized;
use Src\Shared\Core\Errors\ValidationFailed;

describe('Domain errors', function () {
	it('creates not found with correct code status and context', function () {
		$exception = NotFound::entity('user', 'abc-123');

		expect($exception->errorCode())->toBe('user_not_found')
			->and($exception->httpStatus())->toBe(404)
			->and($exception->context())->toBe(['id' => 'abc-123']);
	});

	it('creates validation failed with correct code and status', function () {
		$exception = ValidationFailed::because('email_already_taken');

		expect($exception->errorCode())->toBe('email_already_taken')
			->and($exception->httpStatus())->toBe(422);
	});

	it('creates unauthorized with correct code and status', function () {
		$exception = Unauthorized::because('not_owner');

		expect($exception->errorCode())->toBe('not_owner')
			->and($exception->httpStatus())->toBe(403);
	});
});
