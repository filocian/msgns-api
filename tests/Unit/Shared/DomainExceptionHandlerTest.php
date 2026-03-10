<?php

declare(strict_types=1);

use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\Unauthorized;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Infrastructure\Http\DomainExceptionHandler;

describe('DomainExceptionHandler', function () {
	it('renders not found as 404 json', function () {
		$response = (new DomainExceptionHandler())->render(NotFound::entity('user', 'abc-123'));

		expect($response->status())->toBe(404)
			->and($response->getData(true))->toBe([
				'error' => [
					'code' => 'user_not_found',
					'context' => ['id' => 'abc-123'],
				],
			]);
	});

	it('renders validation failed as 422 json', function () {
		$response = (new DomainExceptionHandler())->render(ValidationFailed::because('email_already_taken'));

		expect($response->status())->toBe(422)
			->and($response->getData(true))->toBe([
				'error' => [
					'code' => 'email_already_taken',
					'context' => [],
				],
			]);
	});

	it('renders unauthorized as 403 json', function () {
		$response = (new DomainExceptionHandler())->render(Unauthorized::because('not_owner'));

		expect($response->status())->toBe(403)
			->and($response->getData(true))->toBe([
				'error' => [
					'code' => 'not_owner',
					'context' => [],
				],
			]);
	});
});
